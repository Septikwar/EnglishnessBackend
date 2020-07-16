<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\CodeGenerator;
use App\Service\Mailer;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException as InvalidArgumentExceptionAlias;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Rest\Route("/api")
 */
class UserController extends AbstractFOSRestController
{
    /**
     * @var UserRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;

    public function __construct(
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $encoder
    )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
    }

    /**
     * @Rest\Post("/user/register")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *             property="user",
     *             type="object",
     *             ref=@Model(type=User::class, groups={})
     *         )
     *     )
     * )
     * @SWG\Tag(name="User")
     * @SWG\Response(
     *     response="200",
     *     description="Создает пользователя",
     *     @Model(type=User::class)
     * )
     * @param Request $request
     * @return Response
     */
    public function registerUser(Request $request, CodeGenerator $codeGenerator, Mailer $mailer)
    {
        $user = new User($this->encoder);

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data);
            if ($form->isValid()) {

                $user->setPassword(
                  $this->encoder->encodePassword(
                      $user,
                      $data['password']
                  )
                );
                $user->setConfirmationCode($codeGenerator->getConfirmationCode());

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $mailer->sendConfirmationMessage($user);

                return new JsonResponse([
                    'data' => [],
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            }
        } catch (UniqueConstraintViolationException $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        } catch (NotNullConstraintViolationException $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        } catch (InvalidArgumentExceptionAlias $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }

        $errors = $this->getErrorsFromForm($form);

        return new JsonResponse([
            'data' => [],
            'errorCode' => 0,
            'errorMsgs' => $errors
        ], 400);
    }

    /**
     * @Rest\Get("/user/confirm/{code}", name="email_confirmation")
     * @SWG\Response(
     *     response="200",
     *     description="Подтверждение E-mail пользователя"
     * )
     * @SWG\Tag(name="User")
     * @param UserRepository $userRepository
     * @param string $code
     * @return Response
     */
    public function confirmEmail(UserRepository $userRepository, string $code)
    {
        /** @var User $user */
        $user = $userRepository->findOneBy(['confirmationCode' => $code]);

        if ($user === null) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не найден'
            ], 404);
        }

        $user->setEnabled(true);
        $user->setConfirmationCode('');

        $em = $this->getDoctrine()->getManager();

        $em->flush();

        return new JsonResponse([
            'data' => [],
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @SWG\Response(
     *     response="200",
     *     description="Сброс пароля пользователя",
     *     @Model(type=User::class)
     * )
     * @SWG\Tag(name="User")
     * @Rest\Post("/user/password/reset")
     * @param Request $request
     */
    public function resetUserPassword(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $account = $data['account'];

        $user = $this->repository->findUserByEmailOrUsername($account);

    }

    private function getErrorsFromForm(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}
