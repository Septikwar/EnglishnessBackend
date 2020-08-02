<?php


namespace App\Controller;


use App\Entity\File;
use App\Form\FileType;
use App\Repository\FileRepository;
use App\Service\Base64FileExtractor;
use App\Service\UploadFile;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface as FormInterfaceAlias;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Rest\Route("/api")
 */
class FileController extends AbstractFOSRestController
{
    CONST IMAGE_PATH = '/uploads/images/';

    /**
     * @var FileRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(FileRepository $repository, EntityManagerInterface $em)
    {
        $this->repository = $repository;
        $this->em = $em;
    }

    /**
     * @SWG\Tag(name="File")
     * @Rest\Post("/file")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="content", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Добавление нового файла"
     * )
     * @param Request $request
     * @return string
     */
    public function addFile(Request $request)
    {
        $file = new File();

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(FileType::class, $file, [
                'method' => 'POST',
                'em' => $this->em
            ]);
            $form->handleRequest($request);

            if (!empty($data['content'])) {
                $data['url'] = $this->fileHandler($data['content']);
            }

            $form->submit($data);
            if ($form->isValid()) {
                $this->em->persist($file);
                $this->em->flush();

                return new JsonResponse([
                    'data' => [
                        'id' => $file->getId(),
                        'url' => $file->getUrl()
                    ],
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }

        return new JsonResponse([
            'data' => '',
            'errorCode' => 0,
            'errorMsgs' => 'Не заполнены обязательные поля'
        ], 400);
    }

    /**
     * @SWG\Tag(name="File")
     * @Rest\Patch("/file/{id}")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="content", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Редактирование файла"
     * )
     * @param Request $request
     * @param $id
     * @return string
     */
    public function editFile(Request $request, $id)
    {
        try {
            $file = $this->repository->findOneById($id);
            $currentPath = $_SERVER['DOCUMENT_ROOT'] . $file->getUrl();
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(FileType::class, $file, [
                'method' => 'POST',
                'em' => $this->em
            ]);
            $form->handleRequest($request);

            if (!empty($data['content'])) {
                $data['url'] = $this->fileHandler($data['content']);
            }

            $form->submit($data);
            if ($form->isValid()) {
                $this->em->persist($file);
                $this->em->flush();

                unlink($currentPath);

                return new JsonResponse([
                    'data' => [
                        'id' => $file->getId(),
                        'url' => $file->getUrl()
                    ],
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }


        return new JsonResponse([
            'data' => '',
            'errorCode' => 0,
            'errorMsgs' => 'Не заполнены обязательные поля'
        ], 400);
    }

    /**
     * @SWG\Tag(name="File")
     * @Rest\Get("/file/{id}")
     * @param $id
     * @SWG\Response(
     *     response="200",
     *     description="Получение файла по айди",
     *     @Model(type=File::class)
     * )
     */
    public function getFile(int $id)
    {
        try {
            $file = $this->repository->findOneById($id);

            if ($file === null) {
                return new JsonResponse([
                    'data' => '',
                    'errorCode' => 0,
                    'errorMsgs' => 'Файл не найден'
                ], 400);
            }

            return new JsonResponse([
                'data' => [
                    'id' => $file->getId(),
                    'url' => $file->getUrl()
                ],
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @SWG\Tag(name="File")
     * @Rest\Delete("/file/{id}")
     * @SWG\Response(
     *     response="200",
     *     description="Удаление файла",
     *     @Model(type=File::class)
     * )
     * @param int $id
     * @return JsonResponse|FormInterfaceAlias
     */
    public function deleteFile(int $id)
    {
        try {
            $file = $this->repository->findOneById($id);

            if ($file !== null) {
                $path = $_SERVER['DOCUMENT_ROOT'] . $file->getUrl();
                $this->repository->deleteFile($id);
                unlink($path);

                return new JsonResponse([
                    'data' => '',
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }

        return new JsonResponse([
            'data' => [],
            'errorCode' => 0,
            'errorMsgs' => 'Файл не найден'
        ], 400);
    }

    private function checkUploadImage($image)
    {
        $decodeImage = (new Base64FileExtractor)->extractBase64String($image);
        $uploadImage = new UploadFile($decodeImage, 'image');
        $mimeType = $uploadImage->getMimeType();

        $errorsImage = [];

        if ($uploadImage->getSize() > (3 * 1024 * 1024)) {
            $errorsImage[] = 'Максимальный размер изображения 3 МБ';
        }

        if ($mimeType !== 'image/png' && $mimeType !== 'image/jpeg') {
            $errorsImage[] = 'Изображение должно быть формата PNG или JPEG';
        }

        if (!empty($errorsImage)) {
            return $errorsImage;
        }

        return $uploadImage;
    }

    private function uploadImage($uploadImage)
    {
        $fileName = md5(uniqid()).'.'.$uploadImage->guessExtension();

        $folder = date('Y') . '/' . date('m');
        $absolutePath = $this->getParameter('image_directory') . '/' . $folder;
        $filenamePath = self::IMAGE_PATH . $folder . '/' . $fileName;

        if (!file_exists($absolutePath)) {
            mkdir($absolutePath, 0777);
        }

        $uploadImage->move($absolutePath, $fileName);

        return $filenamePath;
    }

    private function fileHandler($image)
    {
        $image = $this->checkUploadImage($image);
        if ($image instanceof UploadFile) {
            return $this->uploadImage($image);
        } else {
            return new JsonResponse([
                'data' => '',
                'errorCode' => 0,
                'errorMsgs' => $image
            ], 400);
        }
    }
}