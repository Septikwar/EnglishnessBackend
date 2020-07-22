<?php


namespace App\Form;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ManyToManyEntityViewTransformer implements DataTransformerInterface
{
    /**
     * @var EntityRepository
     */
    private $repository;

    /**
     * @var string
     */
    private $idField;


    public function __construct(EntityRepository $repository, $idField = 'id')
    {
        $this->repository = $repository;
        $this->idField = $idField;
    }


    /**
     * @param mixed $collection The value in the original representation
     *
     * @return mixed The value in the transformed representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return [];
        }

        $ids = [];
        $method = sprintf('get%s', ucfirst($this->idField));

        foreach ($collection as $item) {
            $ids[] = $item->$method();
        }

        return $ids;
    }

    /**
     * @param mixed $value The value in the transformed representation
     *
     * @return mixed The value in the original representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function reverseTransform($value)
    {
        if ('' === $value || null === $value) {
            $array = [];
        } else {
            $array = (array) $value;
        }

        $items = [];
        foreach ($array as $item) {
            $items[] = $this->repository->find($item[$this->idField]);
        }

        return new ArrayCollection($items);
    }
}