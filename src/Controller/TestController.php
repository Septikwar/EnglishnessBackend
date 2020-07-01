<?php

namespace App\Controller;

use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TestController extends AbstractController
{
    /**
     * @Route("/random")
     * @return Response
     * @throws Exception
     */
    public function randomNumber()
    {

        $css = new CssSelectorConverter();
        dd($css->toXPath('div.item > h4 > a'));
        $versionStrategy = new StaticVersionStrategy('v2');
        $defaultPackage = new Package($versionStrategy);
        $namedPackage = [
            'img' => new UrlPackage('https://google.com/', $versionStrategy),
            'doc' => new PathPackage('/somethereUrl/documents', $versionStrategy)
        ];

        $package = new Packages($defaultPackage, $namedPackage);

        echo $package->getUrl('test.css');

        echo '<br>';

        echo $package->getUrl('test.css', 'img');

        echo $package->getUrl('test.css', 'doc');
        $number = random_int(0, 100);

        return $this->render('random/random.html.twig', [
            'number' => $number
        ]);
    }
}