<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @SWG\Response(
     *     response="200",
     *     description="Создает пользователя",
     *     @Model(type=User::class)
     * )
     * @param Request $request
     * @return Response
     */
    public function registerUser(Request $request)
    {
        $user = new User($this->encoder);

        try {
            $data = json_decode($request->getContent(), true);

            $form = $this->createForm(UserType::class, $user);
            $form->submit($data);
            if ($form->isValid()) {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return new Response('Пользователь создан', 200);
            }
        } catch (UniqueConstraintViolationException $e) {
            throw new BadRequestHttpException('Такой пользователь уже существует');
        }

        return $form;
    }

    /**
     * @Rest\Post("/user/password/reset")
     * @param Request $request
     */
    public function resetUserPassword(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        $account = $data['account'];

        $user = $this->repository->findUserByEmailOrUsername($account);

    }
}
