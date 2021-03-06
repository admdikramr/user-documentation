<?hh

namespace Hack\UserDocumentation\API\Examples\Pair\Take;

$p = Pair {'foo', -1.5};

// Taking 0 returns an empty ImmVector
var_dump($p->take(0));

// Taking 1 returns an ImmVector of the first value
var_dump($p->take(1));

// Taking 2 (or more) returns an ImmVector containing both values
var_dump($p->take(2));
