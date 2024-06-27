<?php


namespace API;
;

abstract class Book
{
    private $id;
    private $name;
    private $author;

    private $borrow;

    public function __construct($id, $name, $author, $borrow)
    {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->borrow = $borrow;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getBorrow()
    {
        return $this->borrow;
    }

}

class BorrowBook extends Book
{

    private $borrowedBy;

    public function getBorrowedBy()
    {
        return $this->borrowedBy;
    }

    public function setBorrowedBy(Member $borrowedBy)
    {
        $this->borrowedBy = $borrowedBy;
    }
}

class SaleBook extends Book
{
    private $price;

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }
}

class Member
{
    private $name;
    private $id;

    public function __construct($name, $id)
    {
        $this->name = $name;
        $this->id = $id;
    }
}

class Library
{
    private $books;

    public function getBooks()
    {
        return $this->books;
    }

    public function addBook(Book $book)
    {
        $this->books[] = $book;
    }

    public function borrowBook(Book $book, Member $borrowedBy)
    {
        $this->books[$book->getId()] = $book;
        if ($book->getBorrow()) {
            $book->setBorrowedBy($borrowedBy);
        } else {
            return 'This book is for sale only!';
        }
    }
}

$library = new Library();
$saleBook = new SaleBook(1, "Dune", "Name", false);
$library->addBook($saleBook);
$borrowBook = new BorrowBook(2, "Dune 2", "Name", true);
$library->addBook($borrowBook);

$member = new Member('Name Lastname', 1);

$library->borrowBook($borrowBook, $member);

$borrowBook->getBorrowedBy();