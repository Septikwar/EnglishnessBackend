<?php


namespace App\Controller;


use App\Service\Base64FileExtractor;
use App\Service\UploadFile;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
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
     * @SWG\Tag(name="File")
     * @Rest\Post("/file")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="file", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Добавление нового файла"
     * )
     * @param Request $request
     * @return string
     * TODO редактирование, удаление файла
     */
    public function addFile(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!empty($data['image'])) {
            $image = $this->checkUploadImage($data['image']);
            if ($image instanceof UploadFile) {
                return new JsonResponse([
                    'data' => $this->uploadImage($image),
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            } else {
                return new JsonResponse([
                    'data' => '',
                    'errorCode' => 0,
                    'errorMsgs' => $image
                ], 400);
            }

        }

        return new JsonResponse([
            'data' => '',
            'errorCode' => 0,
            'errorMsgs' => 'Не заполнены обязательные поля'
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

}