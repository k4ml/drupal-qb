This is a simple query builder that can be used to generate sql query compatible with Drupal 6 `db_query()` function.

Example usage:-

<pre>
$query = qb_select()
    ->fields("u", array("name", "mail", "status"))
    ->from("users", "u")
    ->where("u.name = '%s'", array('root'))
    ->where("u.status = 1");
$result = db_query($query->sql(), $query->getArguments());
</pre>

Please take a look in `tests/qb.test` for more usage example.

## Reason
Drupal 7 database API already included query builder and it's been backported to Drupal 6 as [dbtng][1] module. The problem with the module is it created a new database connection instead of using existing one. This query builder try to follow the [dbtng][1] API as close as possible.

[1]:http://drupal.org/project/dbtng
