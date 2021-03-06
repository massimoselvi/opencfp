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

namespace OpenCFP\Domain\Services;

use Illuminate\Support\Collection;

interface TalkFormat
{
    public function formatList(Collection $talkCollection, int $adminUserId, bool $userData = true): Collection;

    public function createdFormattedOutput($talk, int $adminUserId, bool $userData = true);
}
