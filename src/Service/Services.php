<?php


namespace App\Service;


use Symfony\Component\Form\FormInterface;

class Services
{
    public function getErrorsFromForm(FormInterface $form)
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $errors;
    }
}