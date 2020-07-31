<?php


namespace App\Security;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function checkPreAuth(UserInterface $user)
    {
        if (!$user instanceof User) {
            return;
        }


        $this->checkToBan($user);
        $this->checkToDisabled($user);

        return true;
    }

    public function checkPostAuth(UserInterface $user)
    {
        // TODO: Implement checkPostAuth() method.
    }

    private function checkToBan(User $user)
    {
        if ($user->getBan()->count() > 0) {
            $bans = $user->getBan()->filter(function ($ban) {
                if ($ban->getBan() === true) return $ban;
            })->toArray();
            $currentDate = new \DateTime();
            foreach ($bans as $ban) {
                if ($ban->getDate() !== null && $ban->getDate() < $currentDate) {
                    $ban->setBan(false);
                    $this->em->persist($ban);
                    $this->em->flush();
                } else {
                    $message = ($ban->getMessage()) ? " по причине \"{$ban->getMessage()}\"" : '';
                    $date = ($ban->getDate() !== null) ? " до \"{$ban->getDate()->format('d.m.Y')}\"" : " навсегда";
                    throw new BadRequestHttpException("Вы были забанены" . $message . $date, null, 400);
                }
            }
        }
    }

    private function checkToDisabled(User $user)
    {
        if ($user->isEnabled() === false) {
            throw new BadRequestHttpException("Вы не подтвердили пользователя по E-mail", null, 400);
        }
    }
}