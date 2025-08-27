<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleParagraph;
use App\Entity\Template;
use App\Repository\CloudflareIndexRepository;
use App\Repository\OpenSearchIndexRepository;
use App\Service\VectorSearch\RedisSearcher;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use JsonMachine\Exception\SyntaxErrorException;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;
use JsonMachine\JsonDecoder\PassThruDecoder;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DataUploadController extends AbstractController
{
    private $validator;
    private $logger;
    private $entityManager;
    private $redisSearcher;

    public function __construct(ValidatorInterface $validator, LoggerInterface $logger, EntityManagerInterface $entityManager, RedisSearcher $redisSearcher)
    {
        $this->validator = $validator;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->redisSearcher = $redisSearcher;
    }

    public function index(CloudflareIndexRepository $cloudflareIndexRepository, OpenSearchIndexRepository $openSearchIndexRepository): Response
    {
        return $this->render('data_upload/index.html.twig', [
            'title' => 'Data Upload',
            'openaiEmbeddingsModels' => ['text-embedding-3-small', 'text-embedding-3-large', 'text-embedding-ada-002'],
            'cloudflareEmbeddingsModels' => (new \App\Service\Cloudflare\WorkersAI\Client('', ''))->getTextEmbeddingsModels(),
            'cloudflareIndexes' => $cloudflareIndexRepository->findAll(),
            'bgeEmbeddingsModels' => (new \App\Service\BGE\Client(''))->embeddingModels(),
            'openSearchIndexes' => $openSearchIndexRepository->findAll()
        ]);
    }

    public function upload(Request $request)
    {
        try {
            // Request validation
            $errors = $this->validateUploadRequest($this->validator, array_merge($request->request->all(), $request->files->all()));

            if(count($errors) > 0) {
                $messages = [];
                foreach ($errors as $violation) {
                    $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                }

                throw new \Exception('Validation failed: '. implode(' ', $messages));
            }

            $articleFiles = $request->files->get('articles', []);
            $templateFiles = $request->files->get('templates', []);

            $articleExternalIds = [];
            $templateExternalIds = [];

            $articlesCount = 0;
            $templatesCount = 0;

            $this->entityManager->wrapInTransaction(function(EntityManager $entityManager) use ($articleFiles, $templateFiles, &$articlesCount, &$templatesCount, &$articleExternalIds, &$templateExternalIds) {

                /* Articles */

                /** @var UploadedFile $articleFile */
                foreach ($articleFiles as $articleFile) {
                    try {
                        $articleItems = Items::fromFile($articleFile->getPathname(), ['decoder' => new ExtJsonDecoder(true)]);

                        foreach ($articleItems as $rawArticle) {
                            if(is_array($rawArticle)) {
                                // Validate article
                                //$errors = $this->validateArticle($this->validator, $rawArticle);
                                $errors = $this->validateArticle2($this->validator, $rawArticle);

                                if(count($errors) > 0) {
                                    $messages = [];
                                    foreach ($errors as $violation) {
                                        $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
                                    }

                                    throw new \Exception('File \''.$articleFile->getClientOriginalName().'\' validation failed: '. implode(' ', $messages));
                                }

                                /*
                                $article = $entityManager->getRepository(Article::class)->findOneBy(['external_id' => $rawArticle['kb_article']['article_id']]) ?? new Article();
                                $article->setExternalId($rawArticle['kb_article']['article_id']);
                                $article->setExternalSectionId($rawArticle['kb_article']['section_id']);
                                $article->setArticleTitle($rawArticle['kb_article']['article_title']);
                                $article->setArticleTags($rawArticle['kb_article']['article_tags']);
                                $article->setAccessType($rawArticle['kb_article']['access_type']);
                                $article->setActive($rawArticle['kb_article']['active']);
                                $article->setCreatedAt(new \DateTime($rawArticle['kb_article']['created_at']));
                                $article = $this->parseArticle($entityManager, $article, $rawArticle['kb_article']['article_content']);
                                */

                                $article = $entityManager->getRepository(Article::class)->findOneBy(['external_id' => $rawArticle['knowledge_article_id']]) ?? new Article();
                                $article->setExternalId($rawArticle['knowledge_article_id']);
                                $article->setExternalSectionId($rawArticle['knowledge_section_id']);
                                $article->setArticleTitle($rawArticle['title']);
                                $article->setArticleTags(str_replace(['{', '}'], '', $rawArticle['tags']));
                                $article->setActive(true);
                                $article->setCreatedAt((new \DateTime)->setTimestamp($rawArticle['create_tstamp']));
                                $article = $this->parseArticle($entityManager, $article, $rawArticle['content']);

                                $entityManager->persist($article);

                                $articleExternalIds[] = $article->getExternalId();

                                $articlesCount++;
                            }
                        }
                    }
                    catch (SyntaxErrorException $e) {
                        throw new \Exception('File \''.$articleFile->getClientOriginalName().'\' parsing failed: The content of the file should be valid JSON.');
                    }
                }

                /* Templates */

                /** @var UploadedFile $templateFile */
                foreach ($templateFiles as $templateFile) {
                    try {
                        $templateItems = Items::fromFile($templateFile->getPathname(), ['decoder' => new PassThruDecoder]);

                        foreach ($templateItems as $templateGroup) {
                            foreach(Items::fromString($templateGroup, ['decoder' => new ExtJsonDecoder(true)]) as $macrosCategoryId => $macrosCategory) {
                                foreach ($macrosCategory['data'] as $externalTemplateId => $rawTemplate) {
                                    $templateTitle = $rawTemplate['title'];
                                    $templateContent = null;

                                    $template = $entityManager->getRepository(Template::class)->findOneBy(['external_id' => $externalTemplateId]) ??
                                        new Template();

                                    $template->setExternalId($externalTemplateId);
                                    $template->setTemplateTitle($templateTitle);

                                    foreach ($rawTemplate['actions'] as $rawTemplateParagraph) {
                                        if($rawTemplateParagraph['action_destination']) {
                                            if(is_array($rawTemplateParagraph['action_destination'])) {
                                                $templateContent .= implode('\n', array_values($rawTemplateParagraph['action_destination']));
                                            }
                                        }
                                    }

                                    if($templateContent) {
                                        $template = $this->parseTemplate($entityManager, $template, $templateContent);
                                        $entityManager->persist($template);

                                        // if Entity was changed
                                        $uow = $entityManager->getUnitOfWork();
                                        $uow->computeChangeSets();

                                        if($uow->isEntityScheduled($template)) {
                                            $templateExternalIds[] = $template->getExternalId();
                                            $templatesCount++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    catch (SyntaxErrorException $e) {
                        throw new \Exception('File \''.$templateFile->getClientOriginalName().'\' parsing failed: The content of the file should be valid JSON.');
                    }
                }
            });

            $this->entityManager->flush();

            /* Invalidate Embeddings */
            $articleIds = $this->entityManager->createQueryBuilder()
                ->select('a.id')
                ->from('App\Entity\Article', 'a')
                ->where('a.external_id IN (:articleExternalIds)')
                ->setParameter('articleExternalIds', $articleExternalIds, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->getQuery()
                ->getResult(Query::HYDRATE_SCALAR_COLUMN);

            $templateIds = $this->entityManager->createQueryBuilder()
                ->select('a.id')
                ->from('App\Entity\Template', 'a')
                ->where('a.external_id IN (:templateExternalIds)')
                ->setParameter('templateExternalIds', $templateExternalIds, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
                ->getQuery()
                ->getResult(Query::HYDRATE_SCALAR_COLUMN);

            foreach ($articleIds as $articleId) {
                $articleEmbeddingBranches = $this->redisSearcher->scan(RedisSearcher::ROOT.RedisSearcher::DELIMITER.'articles'.RedisSearcher::DELIMITER.$articleId.'*');

                if(isset($articleEmbeddingBranches[1]) && !empty($articleEmbeddingBranches[1])) {
                    $this->redisSearcher->delete($articleEmbeddingBranches[1]);
                }
            }

            foreach ($templateIds as $templateId) {
                $templateEmbeddingBranches = $this->redisSearcher->scan(RedisSearcher::ROOT.RedisSearcher::DELIMITER.'templates'.RedisSearcher::DELIMITER.$templateId.'*');

                if(isset($templateEmbeddingBranches[1]) && !empty($templateEmbeddingBranches[1])) {
                    $this->redisSearcher->delete($templateEmbeddingBranches[1]);
                }
            }
            /**/

            return new JsonResponse([
                'success' => true,
                'message' => 'The data has been successfully saved or updated.',
                'additions' => [
                    'articles_count'=> $articlesCount,
                    'templates_count' => $templatesCount
                ],
            ]);
        }
        catch (\Exception $e) {
            $requestParameters = $request->request->all();
            unset($requestParameters['gpt_api_key']);

            $this->logger->error(json_encode([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $requestParameters
            ], 1));

            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Article $article
     * @param string $articleContent
     * @return Article
     */
    private function parseArticle(EntityManagerInterface $entityManager, Article $article, string $articleContent) : Article
    {
        // Delete Paragraphs of the Article
        $article->getParagraphs()->clear();

        $crawler = new Crawler($articleContent);

        $title = null;
        $content = null;

        // Reading Article template node after node
        foreach ($crawler->filter('body')->children() as $element) {
            $elementCrawler = new Crawler($element);

            if($elementCrawler->getNode(0)->nodeName[0] === 'h') {
                if($content) {
                    $content = preg_replace('/[\r\n]{2,}/', PHP_EOL, $content);

                    $paragraph = new ArticleParagraph();

                    $paragraph->setParagraphTitle($title);
                    $paragraph->setParagraphContent($content);


                    $entityManager->persist($paragraph);

                    $article->addParagraph($paragraph);
                }

                $title = $elementCrawler->text();
                $content = null;
            }
            else {
                $content .= $elementCrawler->text().PHP_EOL;
            }
        }

        // Save last Paragraph
        if($content) {
            $paragraph = new ArticleParagraph();

            $paragraph->setParagraphTitle($title);
            $paragraph->setParagraphContent($content);

            $entityManager->persist($paragraph);

            $article->addParagraph($paragraph);
        }

        return $article;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Template $template
     * @param string $templateParagraphContent
     * @return Template
     */
    private function parseTemplate(EntityManagerInterface $entityManager, Template $template, string $templateParagraphContent): Template
    {
        $crawler = new Crawler($templateParagraphContent);
        $content = $crawler->text();

        if($content) {
            $template->setTemplateContent($content);
        }

        return $template;
    }

    /**
     * @param ValidatorInterface $validator
     * @param array $haystack
     * @return ConstraintViolationListInterface
     * @throws \Exception
     */
    private function validateUploadRequest(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface
    {
        $constraints = [new Collection([
            'allowExtraFields' => false,
            'fields' => [
                'articles' => [new Optional([
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '64M',
                                'mimeTypes' => ['txt' => 'text/*'],
                            ])
                        ],
                    ]),
                    new Count(['max' => 5])
                ])],
                'templates' => [new Optional([
                    new All([
                        'constraints' => [
                            new File([
                                'maxSize' => '64M',
                                'mimeTypes' => ['txt' => 'text/*'],
                            ])
                        ],
                    ]),
                    new Count(['max' => 5])
                ])]
            ],
        ])];

        return $validator->validate($haystack, $constraints);
    }

    /**
     * @param ValidatorInterface $validator
     * @param array $haystack
     * @return ConstraintViolationListInterface
     */
    private function validateArticle(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface
    {
        $constraints = [new Collection([
            'allowExtraFields' => false,
            'fields' => [
                'kb_article' => [new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'article_id' => [new Type(['type' => 'integer'])],
                        'section_id' => [new Optional(new Type(['type' => 'integer']))],
                        'section_id_arr' => [new Optional([new All([
                            'constraints' => [
                                //new Type(['type' => 'integer']),
                            ]
                        ])])],
                        'article_title' => [new Type(['type' => 'string'])],
                        'article_content' => [new Type(['type' => 'string'])],
                        'article_tags' => [new Type(['type' => 'string'])],
                        'access_type' => [new Type(['type' => 'string'])],
                        'active' => [new Type(['type' => 'boolean'])],
                        'created_at' => [new DateTime(['format' => 'D, d M Y H:i:s O'])],
                        'updated_at' => []
                    ]
                ])],
            ]
        ])];

        return $validator->validate($haystack, $constraints);
    }

    private function validateArticle2(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface
    {
        $constraints = [new Collection([
            'allowExtraFields' => false,
            'fields' => [
                'knowledge_article_id' => [new Type(['type' => 'integer'])],
                'knowledge_section_id' => [new Optional(new Type(['type' => 'integer']))],
                'knowledge_section_arr_id' => [new Optional([new Type(['type' => 'string'])])],
                'title' => [new Type(['type' => 'string'])],
                'content' => [new Type(['type' => 'string'])],
                'tags' => [new Type(['type' => 'string'])],
                'create_tstamp' => [new Type(['type' => 'integer'])],
                'update_tstamp' => [new Type(['type' => 'integer'])]
            ]
        ])];

        return $validator->validate($haystack, $constraints);
    }

    private function validateTemplate(ValidatorInterface $validator, array $haystack) : ConstraintViolationListInterface
    {
        $constraints = [new All([
            'constraints' => [
                new Collection([
                    'allowExtraFields' => false,
                    'fields' => [
                        'title' => [new Type(['type' => 'string'])],
                        'sort' => [new Type(['type' => 'integer'])],
                        'macros_category_id' => [new Type(['type' => 'integer'])],
                        'data' => [new All([
                            'constraints' => [
                                new Collection([
                                    'allowExtraFields' => false,
                                    'fields' => [
                                        'title' => [new Type(['type' => 'string'])],
                                        'position' => [new Type(['type' => 'integer'])],
                                        'group_name' => [new Type(['type' => 'string'])],
                                        'actions' => [new All([
                                            'constraints' => [
                                                new Collection([
                                                    'allowExtraFields' => false,
                                                    'fields' => [
                                                        'macro_action_id' => [new Type(['type' => 'integer'])],
                                                        'action_type' => [new Type(['type' => 'string'])],
                                                        'action_display_name' => [new Type(['type' => 'string'])],
                                                        'action_destination' => [new Optional(
                                                            new Callback([
                                                                'callback' => function ($value, $context) {
                                                                    if(!is_array($value) && !is_string($value)) {
                                                                        $context->buildViolation('Field is not array or string.')->addViolation();
                                                                    }
                                                                }
                                                            ])
                                                        )],
                                                        'action_attachments' => [new Optional(new All([
                                                            'constraints' => [
                                                                new Optional(new All([
                                                                    'constraints' => [
                                                                        new Collection([
                                                                            'allowExtraFields' => false,
                                                                            'fields' => [
                                                                                'file_id' => [new Type(['type' => 'integer'])],
                                                                                'file_name' => [new Type(['type' => 'string'])],
                                                                                'file_size' => [new Type(['type' => 'integer'])],
                                                                                'mime_type' => [new Type(['type' => 'string'])],
                                                                                'img_height' => [new Type(['type' => 'integer'])],
                                                                                'img_width' => [new Type(['type' => 'integer'])],
                                                                                'url' => [new Type(['type' => 'string'])]
                                                                            ]
                                                                        ])
                                                                    ]
                                                                ]))
                                                            ]
                                                        ]))],
                                                        'content' => [new Optional(new Type(['type' => 'string']))],
                                                        'subject' => [new Optional(new Type(['type' => 'string']))],
                                                        'position' => [new Optional(new Type(['type' => 'integer']))],
                                                    ]
                                                ])
                                            ]
                                        ])]
                                    ]
                                ])
                            ]
                        ])]
                    ]
                ]),
            ]
        ])];

        return $validator->validate($haystack, $constraints);
    }
}

