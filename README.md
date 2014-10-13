TBD.

To run the tests, go to `tests/bundle/` and issue `./phpunit.sh`.

Still lots to be done here.

* * *

The Mapper should be able to take the various fields in an entity and move them
back and forth between the one or more tables that are the sources for the
entity data.

This means the *gateway* needs to know the primary key and table name and column
names, and the *mapper* needs to know how the fields map to table.column names.

The *gateway* takes arrays and moves them in and out of the database, whereas the
*mapper* talks objects with its callers and arrays with its gateways.

Let's start with single tables first.

```php
<?php
class BlogMapper
{
    protected $field_col = [
        // field => col
        'identity' => 'id',
        'title' => 'title',
        'body' => 'body',
        'author_id' => 'author_id',
    ];

    protected $gateway;

}
?>
```

Do we even really *need* a gateway? Can we instead decorate the query objects with
exec, etc?

* * *

How to incorporate Marshal into this? The mapper fetches only arrays, not entities,
but it receives entities for insert/update/delete.

* * *

Maybe it should fetch entities too, and inject an entity/collection factory.
Then we have the problem of dropping them into the mapper.

Alternatively we have an external Mapper that provides the mapping values, and a
Repository uses a Gateway as the data source, then uses the Mapper to translate
the arrays to and from objects. The Mapper would need the entity/collection
factory.

All that strikes me as kinf of ugly.  What I really want to see is an underlying
Gateway that does *only* arrays, and a layer on top of it that translates the
arrays to and from objects. The problem with that is the objects need to
be marshaled together for aggregates etc.

Maybe the marshal itself is incorrectly put together.

Alternatively, maybe the entity needs a method to get data out of it in a fashion
suitable for the backing store.  It gets created with storage-based array, and
can emit a storage-based array. The table name need not be carried along with it.
The problem then is that we need specific entity types, or a trait that gets used
within the entity.

Or maybe we give up on the idea of the Mapper returning entity objects, and
assume it gets integrated elsewhere with a Repository that does the conversion
using a Marshal or something.

Technically what happens now is the arrays are read out not as entity objects,
but as arrays that use the entity object properties, so they can be dumped directly
into an entity. Is that enough? Would it be enough to return them as POPOs with
the properties already set?