<?php

namespace Pest\Symfony\Tests\App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

final class Book
{
    /**
     * @var string
     * @Assert\Length(min=4)
     */
    public $name;

    /**
     * @var string
     * @Assert\NotNull()
     * @Assert\Isbn()
     */
    public $isbn;
}
