<?php


namespace App\Form;

use Symfony\Component\Form\DataMapperInterface;

class ManyToOneEntityDataMapper implements DataMapperInterface
{
    private $repository;
    private $idField;

    public function __construct($repository, $idField)
    {
        $this->repository = $repository;
        $this->idField = $idField;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($viewData, $forms)
    {
        $methodName = 'get' . ucfirst($this->idField);
        foreach ($forms as $form) {
            if ($form->getName() === $this->idField && $viewData !== null) {
                $form->setData($viewData->$methodName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$viewData)
    {
        $methodName = 'get' . ucfirst($this->idField);
        /* @var Symfony\Component\Form\Form $form */
        foreach ($forms as $form) {
            if ($form->getName() === $this->idField) {
                $id = $form->getViewData();
                if ($id !== null && $id !== '') {
                    if ($viewData->$methodName() != $id) {
                        $entity = $this->repository->find($id);
                        $viewData = $entity;
                    }
                } else {
                    if ($viewData !== null) {
                        $viewData = null;
                    }
                }
                break;
            }
        }
    }
}