<?php

declare(strict_types=1);

use Nfe\Util\ListPage;
use Nfe\Util\ListResponse;

it('carries data + page-style metadata', function (): void {
    $page = new ListPage(pageIndex: 0, pageCount: 20, total: 200);
    $list = new ListResponse(data: ['a', 'b', 'c'], page: $page);

    expect($list->data)->toBe(['a', 'b', 'c']);
    expect($list->page->pageIndex)->toBe(0);
    expect($list->page->pageCount)->toBe(20);
    expect($list->page->total)->toBe(200);
    expect($list->page->startingAfter)->toBeNull();
});

it('carries data + cursor-style metadata', function (): void {
    $page = new ListPage(startingAfter: 'cursor-abc', endingBefore: 'cursor-zzz');
    $list = new ListResponse(data: [], page: $page);

    expect($list->page->startingAfter)->toBe('cursor-abc');
    expect($list->page->endingBefore)->toBe('cursor-zzz');
    expect($list->page->pageIndex)->toBeNull();
});
