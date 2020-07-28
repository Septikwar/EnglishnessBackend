<?php


namespace App\Service;


use App\Entity\User;
use Swift_Message;
use Symfony\Component\HttpFoundation\JsonResponse;
use Twig\Environment;

class Mailer
{
    public const FROM_ADDRESS = 'sscreator@yandex.ru';

    /**
     * @var \Swift_Mailer
     */
    private $swiftMailer;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(\Swift_Mailer $swiftMailer, Environment $twig)
    {
        $this->swiftMailer = $swiftMailer;
        $this->twig = $twig;
    }

    public function sendConfirmationMessage(User $user)
    {
        $messageBody = $this->twig->render('security/confirmation.html.twig', [
            'user' => $user
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Пожалуйста, подтвердите регистрацию')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo($user->getEmail())
            ->setBody($messageBody, 'text/html');

        return $this->swiftMailer->send($message);
    }

    public function sendResetPasswordMessage(User $user)
    {
        $messageBody = $this->twig->render('security/reset.html.twig', [
            'user' => $user
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Запрос на сброс пароля')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo($user->getEmail())
            ->setBody($messageBody, 'text/html');

        return $this->swiftMailer->send($message);
    }

    public function successOfResetPassword(User $user)
    {
        $messageBody = $this->twig->render('security/successReset.html.twig', [
            'user' => $user
        ]);

        $message = new Swift_Message();
        $message
            ->setSubject('Ваш пароль успешно изменен')
            ->setFrom(self::FROM_ADDRESS)
            ->setTo($user->getEmail())
            ->setBody($messageBody, 'text/html');

        return $this->swiftMailer->send($message);
    }

}