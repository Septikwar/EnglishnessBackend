<?php


namespace App\Controller;

use App\Form\WordType;
use App\Service\Services;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Repository\WordRepository;
use App\Entity\Word;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface as FormInterfaceAlias;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


/**
 * @Rest\Route("/api")
 */
class WordController extends AbstractFOSRestController
{
    /**
     * @var WordRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Services
     */
    private $services;

    public function __construct(
        WordRepository $repository,
        EntityManagerInterface $em,
        Services $services)
    {
        $this->repository = $repository;
        $this->em = $em;
        $this->services = $services;
    }

    /**
     * @SWG\Tag(name="Words")
     * @Rest\Get("/word/groups")
     * @Rest\QueryParam(
     *     name="id",
     *     nullable=false
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Вывод слов в выбранных группах",
     *     @Model(type=Word::class)
     * )
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    public function getAllWordsInGroups(ParamFetcherInterface $paramFetcher)
    {
        $repository = $this->repository;

        $ids = $paramFetcher->get('id');

        if (is_array($ids)) {
            foreach ($ids as $key => $val) {
                if (!is_numeric($val)) {
                    return new JsonResponse([
                        'data' => '',
                        'errorCode' => 0,
                        'errorMsgs' => 'В id могут находиться только целые числа'
                    ], 400);
                }
            }
        } else {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => 'Не передан массив id'
            ], 400);
        }

        $data = $repository->findAllWordsInGroups($ids);

        return new JsonResponse([
            'data' => $data,
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @Rest\View(serializerGroups={"Word"})
     * @SWG\Tag(name="Words")
     * @Rest\Get("/word/{id}")
     * @Rest\QueryParam(
     *     name="id",
     *     requirements="\d",
     *     nullable=false,
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Вывод слова",
     *     @Model(type=Word::class)
     * )
     * @param $id
     * @return JsonResponse
     */
    public function getWord(int $id)
    {
        $repository = $this->repository;

        $data = $repository->findById($id);

        if (!empty($data)) {
            return new JsonResponse([
                'data' => $data,
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        } else {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => 'Ничего не найдено'
            ], 404);
        }
    }

    /**
     * @Rest\View(serializerGroups={"Word"})
     * @SWG\Tag(name="Words")
     * @Rest\Get("/word")
     * @Rest\QueryParam(
     *     name="pagesize",
     *     requirements="\d+",
     *     nullable=false,
     *     default="10",
     *     strict=true
     * )
     * @Rest\QueryParam(
     *     name="page",
     *     requirements="\d+",
     *     default="1",
     *     strict=true
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Вывод всех слов",
     *     @Model(type=Word::class)
     * )
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    public function getAllWords(ParamFetcherInterface $paramFetcher)
    {
        $repository = $this->repository;

        $data = $repository->findAllWords(
            $paramFetcher->get('pagesize'),
            $paramFetcher->get('page')
        );

        return new JsonResponse([
            'data' => $data,
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @Rest\View(serializerGroups={"Word"})
     * @SWG\Tag(name="Words")
     * @Rest\Post("/word")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="en", type="string"),
     *         @SWG\Property(property="ru", type="string"),
     *         @SWG\Property(property="groups", type="array",
     *             @SWG\Items(
     *                 @SWG\Property(property="id", type="integer"),
     *             )
     *         )
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Создает новое слово",
     *     @Model(type=Word::class)
     * )
     * @param Request $request
     * @return FormInterfaceAlias|JsonResponse
     */
    public function createWord(Request $request)
    {
        $word = new Word();

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(WordType::class, $word, [
                'method' => 'POST',
                'em' => $this->em
            ]);
            $form->handleRequest($request);
            $form->submit($data);
            if ($form->isValid()) {
                $this->em->persist($word);
                $this->em->flush();

                return $this->getWord($word->getId());
            }

            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => 'Ошибка валидации'
            ], 400);

        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => $e->getMessage(),
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 400);
        }
    }

    /**
     * @Rest\View(serializerGroups={"Word"})
     * @SWG\Tag(name="Words")
     * @Rest\Patch("/word/{id}")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="en", type="string"),
     *         @SWG\Property(property="ru", type="string"),
     *         @SWG\Property(property="groups", type="array",
     *             @SWG\Items(
     *                 @SWG\Property(property="id", type="integer"),
     *             )
     *         )
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Редактирует слово",
     *     @Model(type=Word::class)
     * )
     * @param Request $request
     * @param int $id
     * @return JsonResponse|FormInterfaceAlias
     */
    public function editWord(Request $request, int $id)
    {
        try {
            $word = $this->repository->findById($id, true);

            if (empty($word)) {
                return new JsonResponse([
                    'data' => [],
                    'errorCode' => 0,
                    'errorMsgs' => 'Слово не найдена'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(WordType::class, $word, [
                'method' => 'PATCH',
                'em' => $this->em
            ]);
            $form->handleRequest($request);
            $form->submit($data);
            if ($form->isValid()) {
                $this->em->persist($word);
                $this->em->flush();

                return $this->getWord($word->getId());
            }

            $errors = $this->services->getErrorsFromForm($form);

            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $errors
            ], 400);

        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @SWG\Tag(name="Words")
     * @Rest\Delete("/word/{id}")
     * @SWG\Response(
     *     response="200",
     *     description="Удаление слова",
     *     @Model(type=Word::class)
     * )
     * @param int $id
     * @return JsonResponse|FormInterfaceAlias
     */
    public function deleteWord(int $id)
    {
        try {
            $this->repository->deleteWord($id);

            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }
    }
}