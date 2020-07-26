<?php


namespace App\Controller;


use App\Entity\WordGroup;
use App\Form\WordGroupType;
use App\Repository\WordGroupRepository;
use App\Service\Base64FileExtractor;
use App\Service\Services;
use App\Service\UploadFile;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface as FormInterfaceAlias;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Json;

/**
 * @Rest\Route("/api")
 */
class WordGroupController extends AbstractFOSRestController
{

    CONST IMAGE_PATH = 'uploads/images/';

    /**
     * @var WordGroupRepository
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
        WordGroupRepository $repository,
        EntityManagerInterface $em,
        Services $services
    )
    {
        $this->repository = $repository;
        $this->em = $em;
        $this->services = $services;
    }

    /**
     * @SWG\Tag(name="Groups")
     * @Rest\Get("/group/{id}")
     * @param $id
     * @SWG\Response(
     *     response="200",
     *     description="Вывод группы и относящихся к ней слов",
     *     @Model(type=WordGroup::class)
     * )
     */
    public function getWordGroup(int $id)
    {
        $repository = $this->repository;

        $data = $repository->findById($id);
        return new JsonResponse([
            'data' => $data,
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @SWG\Tag(name="Groups")
     * @Rest\Get("/group")
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
     *     description="Вывод всех групп",
     *     @Model(type=WorgGroup::class)
     * )
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    public function getAllWordGroups(ParamFetcherInterface $paramFetcher)
    {
        $repository = $this->repository;

        $data = $repository->findAllGroups(
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
     * @SWG\Tag(name="Groups")
     * @Rest\Post("/group")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="name", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Создает группу слов",
     *     @Model(type=WordGroup::class)
     * )
     * @param Request $request
     * @return WordGroup|FormInterfaceAlias
     */
    public function createWordGroup(Request $request)
    {
        $wordGroup = new WordGroup();

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(WordGroupType::class, $wordGroup, [
                'method' => 'POST',
                'em' => $this->em
            ]);
            $form->handleRequest($request);
            $form->submit($data);
            if ($form->isValid()) {


                if (!empty($data['image'])) {
                    $decodeImage = (new Base64FileExtractor)->extractBase64String($data['image']);
                    $uploadImage = new UploadFile($decodeImage, 'image');
                    $fileName = md5(uniqid()).'.'.$uploadImage->guessExtension();
                    $uploadImage->move($this->getParameter('image_directory'), $fileName);
                    $wordGroup->setImage(self::IMAGE_PATH.$fileName);
                }

                $this->em->persist($wordGroup);
                $this->em->flush();

                return $this->getWordGroup($wordGroup->getId());
            }
            return $form;
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => $e->getMessage(),
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        }
    }

    /**
     * @SWG\Tag(name="Groups")
     * @Rest\Patch("/group/{id}")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="name", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Редактирует группу слов",
     *     @Model(type=WordGroup::class)
     * )
     * @param Request $request
     * @param int $id
     * @return JsonResponse|FormInterfaceAlias
     */
    public function editWordGroup(Request $request, int $id)
    {
        try {
            $wordGroup = $this->repository->findById($id, true);

            if (empty($wordGroup)) {
                return new JsonResponse([
                    'data' => [],
                    'errorCode' => 0,
                    'errorMsgs' => 'Группа не найдена'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(WordGroupType::class, $wordGroup, [
                'method' => 'PATCH',
                'em' => $this->em
            ]);
            $form->handleRequest($request);
            $form->submit($data);
            if ($form->isValid()) {

                if (!empty($data['image'])) {
                    $decodeImage = (new Base64FileExtractor)->extractBase64String($data['image']);
                    $uploadImage = new UploadFile($decodeImage, 'image');
                    $fileName = md5(uniqid()).'.'.$uploadImage->guessExtension();
                    $uploadImage->move($this->getParameter('image_directory'), $fileName);
                    $wordGroup->setImage(self::IMAGE_PATH.$fileName);
                }

                $this->em->persist($wordGroup);
                $this->em->flush();

                return $this->getWordGroup($wordGroup->getId());
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
     * @SWG\Tag(name="Groups")
     * @Rest\Delete("/group/{id}")
     * @SWG\Response(
     *     response="200",
     *     description="Удаление группы слов",
     *     @Model(type=WordGroup::class)
     * )
     * @param int $id
     * @return JsonResponse|FormInterfaceAlias
     */
    public function deleteWordGroup(int $id)
    {
        try {
            $this->repository->deleteWordGroup($id);

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
