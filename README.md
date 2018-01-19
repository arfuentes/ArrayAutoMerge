# ArrayAutoMerge
A PHP class to transform SQL query results from several nested tables to nested arrays.

Using a delimiter character (usually the underscore character producing snake_case) in the names of the returning fields in a SQL query, it transforms the PHP array obtained from the records dataset in a complex nested array by __auto merging the sections of the array with equal values__. The array is sectioned based on the array keys that share the same prefixed before each delimiter character, recursively.

#### Dependencies
* `PHP >= 5.4`

## Introduction
In my experience as a C# developer I have used [Slapper.AutoMapper](https://github.com/SlapperAutoMapper/Slapper.AutoMapper) in several projects. It’s an interesting library to convert dynamic data into static types, and populate complex nested objects.

Its main application lies in the mapping of datasets to strongly typed objects, no matter their level of nested child objects, being very useful when the dataset is generating from a SQL query that comprises columns from several nested tables. The library solves these mappings by using the underscore notation, where every underscore character implies the existence of a nested object, that can be a single object or a collection. The deduplication process at the distinct levels of the object hierarchy is based on the primary keys, using an internal cache to easily lookup existing objects and increase efficiency when working with huge volume of data.

As a novice PHP developer, I needed to emulate this library for a project I was working on, where I needed to execute several complex queries and map the results to nested arrays. I searched for a generic solution to do the work no matter the complexity of the dataset, but I couldn’t find any.

__ArrayAutoMerge__ is a small class that solves this problem. Its instantiation requires to define the name of the primary key to be used in every section that will be transformed in a nested array (usually `id` or `Id`), and the delimiter character that defines the sections (usually underscore `_`). It has only one public method `auto_merge`, where the input is the array you want to transform, and the output is the transformed array.

## Usage
In my particular case I used this class in a _codeigniter_ project, calling `auto_merge` passing the output of the method `result_array()`, which returns the query result as a pure array; but it’s quite normal in PHP to execute queries against a database and return the dataset as an array of records, where every record is an array with the field names as the keys.

Let’s consider the following PostgreSQL query based on the [chinook database](https://github.com/lerocha/chinook-database), which takes all the customers, invoices and invoice lines for all the invoices created in 2013 with billing country equal to United Kingdom:
```sql
SELECT c."CustomerId"    AS "Id",
       c."FirstName",
       c."LastName",
       i."InvoiceId"     AS "Invoices_Id",
       i."InvoiceDate"   AS "Invoices_Date",
       l."InvoiceLineId" AS "Invoices_Lines_Id",
       t."Name"          AS "Invoices_Lines_Track",
       l."UnitPrice"     AS "Invoices_Lines_UnitPrice",
       l."Quantity"      AS "Invoices_Lines_Quantity"
FROM "Customer" c 
     JOIN "Invoice" i     USING ("CustomerId")
     JOIN "InvoiceLine" l USING ("InvoiceId")
     JOIN "Track" t       USING ("TrackId")
WHERE EXTRACT(YEAR FROM i."InvoiceDate") = 2013 
      AND i."BillingCountry" = 'United Kingdom'
ORDER BY c."LastName", c."FirstName", i."InvoiceId", t."TrackId"
```
When executing the query, the results contains **29 records**, but there are only **3 different customers** and **6 different invoices**.
The result when executing the query should be an array of arrays like this one:
```php
array(
  array(
    'Id' => 53,
    'FirstName' => 'Phil',
    'LastName' => 'Hughes',
    'Invoices_Id' => 335,
    'Invoices_Date' => '15/01/2013',
    'Invoices_Lines_Id' => 1822,
    'Invoices_Lines_Track' => 'Blue Rythm Fantasy',
    'Invoices_Lines_UnitPrice' => 0.99,
    'Invoices_Lines_Quantity' => 1
  ),
  array(
    'Id' => 52,
    'FirstName' => 'Emma',
    'LastName' => 'Jones',
    'Invoices_Id' => 358,
    'Invoices_Date' => '01/05/2013',
    'Invoices_Lines_Id' => 1939,
    'Invoices_Lines_Track' => 'Tailgunner',
    'Invoices_Lines_UnitPrice' => 0.99,
    'Invoices_Lines_Quantity' => 1
  ),
  array(
    'Id' => 52,
    'FirstName' => 'Emma',
    'LastName' => 'Jones',
    'Invoices_Id' => 358,
    'Invoices_Date' => '01/05/2013',
    'Invoices_Lines_Id' => 1940,
    'Invoices_Lines_Track' => 'No Prayer For The Dying',
    'Invoices_Lines_UnitPrice' => 0.99,
    'Invoices_Lines_Quantity' => 1
  ),
  array('Id' => 52,
    'FirstName' => 'Emma',
    'LastName' => 'Jones',
    'Invoices_Id' => 369,
    'Invoices_Date' => '11/06/2013',
    'Invoices_Lines_Id' => 1998,
    'Invoices_Lines_Track' => 'Sick Again',
    'Invoices_Lines_UnitPrice' => 0.99,
    'Invoices_Lines_Quantity' => 1
  ),
  ...
)
```
When using __ArrayAutoMerge__, the transformed array should be something like:
```php
array(
  array(
    'Id' => 53,
    'FirstName' => 'Phil',
    'LastName' => 'Hughes',
    'Invoices' => array(
      array(
        'Id' => 335,
        'Date' => '15/01/2013',
        'Lines' => array(
          array(
            'Id' => 1822,
            'Track' => 'Blue Rythm Fantasy',
            'UnitPrice' => 0.99,
            'Quantity' => 1
          )
        )  
      )
    )
  ),
  array(
    'Id' => 52,
    'FirstName' => 'Emma',
    'LastName' => 'Jones',
    'Invoices' => array(
      array(
        'Id' => 358,
        'Date' => '01/05/2013',
        'Lines' => array(
          array(
            'Id' => 1939,
            'Track' => 'Tailgunner',
            'UnitPrice' => 0.99,
            'Quantity' => 1,
          ),
          array(
            'Id' => 1940,
            'Track' => 'No Prayer For The Dying',
            'UnitPrice' => 0.99,
            'Quantity' => 1,
          )
        )
      ),
      array(
        'Id' => 369,
        'Date' => '11/06/2013',
        'Lines' => array(
          array(
            'Id' => 1998,
            'Track' => 'Sick Again',
            'UnitPrice' => 0.99,
            'Quantity' => 1
          ),
          array(
            'Id' => 1999,
            'Track' => 'Celebration Day',
            'UnitPrice' => 0.99,
            'Quantity' => 1,
          ),
          array(
            'Id' => 2000,
            'Track' => 'LAvventura',
            'UnitPrice' => 0.99,
            'Quantity' => 1,
          ),
          ...
        )
      )
      ...
    )
  )
  ...
)
```
All duplicate data have been removed, and now you can iterate over the external array to list customers, and for each customer you can iterate over his `Invoices` array to list customer’s invoices, and so on.

You can run the __test.php__ with the whole array, to get a clearer vision of the process result.

It’s not necessary that every section in the hierarchy have a primary key, but, in those cases, there will be no process of deduplication for the corresponding section in the array hierarchy. 

Notice that __ArrayAutoMerge__ can be used for `LEFT JOIN` relationships, when all the fields on the right side could be null, in such a case the corresponding nested array in the output will contain no elements instead of arrays of null entries. When using a primary key, only the nullability of the primary key will be analysed, that’s why it’s advisable that every section have a primary key.

For simplification purposes, the class doesn’t support:
-	Composite primary keys. As an alternative solution, if any of the tables used in the query has a composite primary key, you can generate a single field with the name of the key settled in __ArrayAutoMerge__ constructor by, for example, concatenating all the primary key values of the table. 
-	A primary key name different from the settled one to deduplicate. As an alternative solution you can duplicate the key field, using one with the name you really want in the output and the other one with the settled name for duplication purposes.

As the class is intended to work only with arrays, every section is transformed in an array of arrays, even when the array contains only one element; so, if you decide that some fields to be grouped in a section, being aware that it’s not really a collection, but a single element (usually in many-to-one relationships), the transformation will generate an array, and you should take the first element of the array. For example, if you are listing invoices, and for each invoice you want to show the information of the customer using the section `_Customer`, __ArrayAutoMerge__ will generate an array of customers, even when you know there’s only one per invoice, but, as you have a good understanding of your model and build the SQL query using a known logic, you can simply take the first element of the array.

The hierarchy doesn’t need to be strictly vertical, it can contain ramifications at any levels, for example, a customer can have a collection of emails and a collection of phones as well.


## Disclaimer

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.

## License
The MIT License (MIT)

Copyright (c) 2018 Anibal Rodriguez Fuentes

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
