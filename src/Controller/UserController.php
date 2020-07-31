<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserBans;
use App\Form\User\BanType;
use App\Form\User\UserResetPasswordType;
use App\Form\User\UserType;
use App\Repository\UserBansRepository;
use App\Repository\UserRepository;
use App\Service\CodeGenerator;
use App\Service\Mailer;
use App\Service\Services;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException as InvalidArgumentExceptionAlias;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

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

    /**
     * @var Services
     */
    private $services;

    /**
     * @var CodeGenerator
     */
    private $codeGenerator;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var Security
     */
    private $security;

    public function __construct(
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $encoder,
        Services $services,
        CodeGenerator $codeGenerator,
        Mailer $mailer,
        Security $security
    )
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
        $this->services = $services;
        $this->codeGenerator = $codeGenerator;
        $this->mailer = $mailer;
        $this->security = $security;
    }

    /**
     * @SWG\Tag(name="User")
     * @Rest\Post("/user/login")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="username", type="string"),
     *         @SWG\Property(property="email", type="string"),
     *         @SWG\Property(property="password", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Авторизация пользователя"
     * )
     * @return void
     */
    public function loginUser()
    {}

    /**
     * @Rest\Post("/user/register")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="username", type="string"),
     *         @SWG\Property(property="email", type="string"),
     *         @SWG\Property(property="password", type="string")
     *     )
     * )
     * @SWG\Tag(name="User")
     * @SWG\Response(
     *     response="200",
     *     description="Создает пользователя"
     * )
     * @param Request $request
     * @return Response
     */
    public function registerUser(Request $request)
    {
        $user = new User();

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
                $user->setConfirmationCode($this->codeGenerator->getConfirmationCode());

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->mailer->sendConfirmationMessage($user);

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

        $errors = $this->services->getErrorsFromForm($form);

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
     * @param string $code
     * @return Response
     */
    public function confirmEmail(string $code)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['confirmationCode' => $code]);

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
     * @SWG\Tag(name="User")
     * @Rest\Post("/user/reset")
     * @SWG\Response(
     *     response="200",
     *     description="Сброс пароля пользователя",
     *     @Model(type=User::class)
     * )
     * @param Request $request
     * @return JsonResponse
     */
    public function resetUserPassword(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || (empty($data['email']))) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Неверный формат переданных данных'
            ], 400);
        }

        $user = $this->repository->findUserByEmail($data['email']);

        if ($user instanceof User) {
            $user->setConfirmationCode($this->codeGenerator->getConfirmationCode());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->mailer->sendResetPasswordMessage($user);

            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        }
    }

    /**
     * @Rest\Get("/user/reset/{code}", name="password_reset")
     * @SWG\Response(
     *     response="200",
     *     description="Вывод формы для сброса пароля"
     * )
     * @SWG\Tag(name="User")
     * @param string $code
     * @return Response
     */
    public function formToResetPassword(string $code)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['confirmationCode' => $code]);

        if ($user === null) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не найден'
            ], 404);
        }

        return new JsonResponse([
            'data' => [],
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @Rest\Post("/user/reset/{code}")
     * @SWG\Response(
     *     response="200",
     *     description="Сброс пароля пользователя"
     * )
     * @SWG\Tag(name="User")
     * @param Request $request
     * @param string $code
     * @return Response
     */
    public function userResetPassword(Request $request, string $code)
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['confirmationCode' => $code]);

        if ($user === null) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не найден'
            ], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(UserResetPasswordType::class, $user);
            $form->submit($data);
            if ($form->isValid()) {
                $user->setPassword(
                    $this->encoder->encodePassword(
                        $user,
                        $data['password']['password']
                    )
                );
                $user->setConfirmationCode('');

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->mailer->successOfResetPassword($user);

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
        } catch (\Exception $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => $e->getMessage()
            ], 400);
        }

        $errors = $this->services->getErrorsFromForm($form);

        return new JsonResponse([
            'data' => [],
            'errorCode' => 0,
            'errorMsgs' => $errors
        ], 400);
    }

    /**
     * @View(serializerGroups={"public"})
     * @Rest\Get("/user/current")
     * @SWG\Response(
     *     response="200",
     *     description="Вывод информации о текущем пользователе"
     * )
     * @SWG\Tag(name="User")
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getCurrentUserInfo(SerializerInterface $serializer)
    {
        $user = $this->security->getUser();

        if (!isset($user) || empty($user)) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не авторизован'
            ], 400);
        }

        return new JsonResponse([
            'data' => $serializer->normalize($user, 'json', ['groups' => 'public']),
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @View(serializerGroups={"public"})
     * @Rest\Get("/user")
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
     *     description="Вывод всех юзеров"
     * )
     * @SWG\Tag(name="User")
     * @param SerializerInterface $serializer
     * @return Response
     */
    public function getAllUsers(SerializerInterface $serializer, ParamFetcherInterface $paramFetcher)
    {
        $users = $this->repository->findAllUsers(
            $paramFetcher->get('pagesize'),
            $paramFetcher->get('page')
        );

        return new JsonResponse([
            'data' => $serializer->normalize($users, 'json', ['groups' => 'public']),
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @View(serializerGroups={"public"})
     * @Rest\Get("/user/{id}")
     * @SWG\Response(
     *     response="200",
     *     description="Вывод информации о пользователе по айди"
     * )
     * @SWG\Tag(name="User")
     * @param SerializerInterface $serializer
     * @param int $id
     * @return Response
     */
    public function getUserInfo(SerializerInterface $serializer, int $id)
    {
        $user = $this->repository->findOneById($id);

        if (!isset($user) || empty($user)) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Пользователь не найден'
            ], 400);
        }

        $currentUser = $this->security->getUser();

        if ($currentUser === $user || in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            return new JsonResponse([
                'data' => $serializer->normalize($user, 'json', ['groups' => ['public', 'current', 'admin']]),
                'errorCode' => 0,
                'errorMsgs' => ''
            ], 200);
        }
        return new JsonResponse([
            'data' => $serializer->normalize($user, 'json', ['groups' => 'public']),
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @View(serializerGroups={"public"})
     * @Rest\Post("/user/ban")
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     @SWG\Schema(
     *         @SWG\Property(property="userId", type="string"),
     *         @SWG\Property(property="message", type="string"),
     *         @SWG\Property(property="date", type="string")
     *     )
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Бан юзера"
     * )
     * @SWG\Tag(name="User")
     * @param Request $request
     * @return Response
     */
    public function banUser(Request $request)
    {
        $ban = new UserBans();

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(BanType::class, $ban);

            $form->submit($data);
            if ($form->isValid()) {
                $ban->setBan(true);
                $this->entityManager->persist($ban);
                $this->entityManager->flush();

                return new JsonResponse([
                    'data' => [],
                    'errorCode' => 0,
                    'errorMsgs' => ''
                ], 200);
            }
        } catch (NotNullConstraintViolationException $e) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Вы не заполнили обязательные поля'
            ], 400);
        }

        return $form;
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @View(serializerGroups={"public"})
     * @Rest\Post("/user/unban/{id}")
     * @SWG\Response(
     *     response="200",
     *     description="Анбан юзера"
     * )
     * @SWG\Tag(name="User")
     * @param UserBansRepository $userBansRepository
     * @param int $id
     * @return Response
     */
    public function unbanUser(UserBansRepository $userBansRepository, int $id)
    {
        $ban = $userBansRepository->findOneById($id);

        if (!isset($ban) || empty($ban)) {
            return new JsonResponse([
                'data' => [],
                'errorCode' => 0,
                'errorMsgs' => 'Бан не найден'
            ], 400);
        }

        $ban->setBan(false);
        $this->entityManager->persist($ban);
        $this->entityManager->flush();
        return new JsonResponse([
            'data' => '',
            'errorCode' => 0,
            'errorMsgs' => ''
        ], 200);
    }
}
