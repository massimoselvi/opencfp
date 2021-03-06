<?php

declare(strict_types=1);

/**
 * Copyright (c) 2013-2017 OpenCFP
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/opencfp/opencfp
 */

namespace OpenCFP\Http\Controller\Reviewer;

use Illuminate\Support\Collection;
use OpenCFP\Domain\Model\Favorite;
use OpenCFP\Domain\Model\Talk;
use OpenCFP\Domain\Model\User;
use OpenCFP\Domain\Services\Authentication;
use OpenCFP\Domain\Talk\TalkFormatter;
use OpenCFP\Http\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig_Environment;

class DashboardController extends BaseController
{
    /**
     * @var Authentication
     */
    private $authentication;

    public function __construct(Authentication $authentication, Twig_Environment $twig, UrlGeneratorInterface $urlGenerator)
    {
        $this->authentication = $authentication;

        parent::__construct($twig, $urlGenerator);
    }

    public function indexAction(): Response
    {
        $userId        = $this->authentication->user()->getId();
        $talkFormatter = new TalkFormatter();

        /** @var Collection $recentTalks */
        $recentTalks = Talk::recent()->get();
        $recentTalks = $talkFormatter->formatList($recentTalks, $userId);

        return $this->render('reviewer/index.twig', [
            'speakerTotal'  => User::count(),
            'talkTotal'     => Talk::count(),
            'favoriteTotal' => Favorite::count(),
            'selectTotal'   => Talk::where('selected', 1)->count(),
            'talks'         => $recentTalks,
        ]);
    }
}
