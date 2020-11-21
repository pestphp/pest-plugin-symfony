<?php

namespace Pest\Symfony\Tests\Plugin;

use function Pest\Symfony\assertViolated;
use Pest\Symfony\Tests\App\Entity\Book;

it('asserts a subject\'s constraints have been violated', function () {
    $book = new Book();
    assertViolated(true, $book);
    assertViolated(false, $book, 'name');
    assertViolated(true, $book, 'isbn');
    assertViolated(1, $book);
    assertViolated(0, $book, 'name');
    assertViolated(1, $book, 'isbn');
    assertViolated('isbn: This value should not be null.', $book);
    assertViolated('isbn: This value should not be null.', $book, 'isbn');

    $book->name = 'foo';
    assertViolated(true, $book);
    assertViolated(true, $book, 'name');
    assertViolated(true, $book, 'isbn');
    assertViolated(2, $book);
    assertViolated(1, $book, 'name');
    assertViolated(1, $book, 'isbn');
    assertViolated('isbn: This value should not be null.', $book);
    assertViolated('isbn: This value should not be null.', $book, 'isbn');
    assertViolated('name: This value is too short. It should have 4 characters or more.', $book, 'name');

    $book->name = '1984';
    assertViolated(true, $book);
    assertViolated(false, $book, 'name');
    assertViolated(true, $book, 'isbn');
    assertViolated(1, $book);
    assertViolated(0, $book, 'name');
    assertViolated(1, $book, 'isbn');
    assertViolated('isbn: This value should not be null.', $book);
    assertViolated('isbn: This value should not be null.', $book, 'isbn');

    $book->isbn = 'foobar';
    assertViolated(true, $book);
    assertViolated(false, $book, 'name');
    assertViolated(true, $book, 'isbn');
    assertViolated(1, $book);
    assertViolated(0, $book, 'name');
    assertViolated(1, $book, 'isbn');
    assertViolated('isbn: This value is neither a valid ISBN-10 nor a valid ISBN-13.', $book);
    assertViolated('isbn: This value is neither a valid ISBN-10 nor a valid ISBN-13.', $book, 'isbn');

    $book->isbn = '978-0451524935';
    assertViolated(false, $book);
    assertViolated(false, $book, 'name');
    assertViolated(false, $book, 'isbn');
    assertViolated(0, $book);
    assertViolated(0, $book, 'name');
    assertViolated(0, $book, 'isbn');
});
