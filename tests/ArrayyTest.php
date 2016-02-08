<?php

use Arrayy\Arrayy as A;

/**
 * Class ArrayyTestCase
 */
class ArrayyTest extends PHPUnit_Framework_TestCase
{
  const TYPE_EMPTY   = 'empty';
  const TYPE_NUMERIC = 'numeric';
  const TYPE_ASSOC   = 'assoc';
  const TYPE_MIXED   = 'mixed';

  public function testConstruct()
  {
    $testArray = array('foo bar', 'UTF-8');
    $arrayy = new A($testArray);
    self::assertArrayy($arrayy);
    self::assertEquals('foo bar,UTF-8', (string)$arrayy);
  }

  /**
   * Asserts that a variable is of a Arrayy instance.
   *
   * @param mixed $actual
   */
  public function assertArrayy($actual)
  {
    self::assertInstanceOf('Arrayy\Arrayy', $actual);
  }

  public function testSetV2()
  {
    $arrayy = new A(array('foo bar', 'UTF-8'));
    $arrayy[1] = 'öäü';
    self::assertArrayy($arrayy);
    self::assertEquals('foo bar,öäü', (string)$arrayy);
  }

  public function testGet()
  {
    $arrayy = new A(array('foo bar', 'öäü'));
    self::assertArrayy($arrayy);
    self::assertEquals('öäü', $arrayy[1]);
  }

  public function testUnset()
  {
    $arrayy = new A(array('foo bar', 'öäü'));
    unset($arrayy[1]);
    self::assertArrayy($arrayy);
    self::assertEquals('foo bar', $arrayy[0]);
    self::assertEquals(null, $arrayy[1]);
  }

  public function testIsSet()
  {
    $arrayy = new A(array('foo bar', 'öäü'));
    self::assertArrayy($arrayy);
    self::assertEquals(true, isset($arrayy[0]));
  }

  public function testForEach()
  {
    $arrayy = new A(array(1 => 'foo bar', 'öäü'));

    foreach ($arrayy as $key => $value) {
      if ($key === 1) {
        self::assertEquals('foo bar', $arrayy[$key]);
      } elseif ($key === 2) {
        self::assertEquals('öäü', $arrayy[$key]);
      }
    }

  }

  public function testEmptyConstruct()
  {
    $arrayy = new A();
    self::assertArrayy($arrayy);
    self::assertEquals('', (string)$arrayy);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testConstructWithArray()
  {
    new A(5);
    static::fail('Expecting exception when the constructor is passed an array');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testMissingToString()
  {
    /** @noinspection PhpExpressionResultUnusedInspection */
    (string)new A(new stdClass());
    static::fail(
        'Expecting exception when the constructor is passed an ' .
        'object without a __toString method'
    );
  }

  /**
   * @dataProvider toStringProvider()
   *
   * @param       $expected
   * @param array $array
   */
  public function testToString($expected, $array)
  {
    self::assertEquals($expected, (string)new A($array));
  }

  /**
   * @return array
   */
  public function toStringProvider()
  {
    return array(
        array('', array(null)),
        array('', array(false)),
        array('1', array(true)),
        array('-9,1,0,', array(-9, 1, 0, false)),
        array('1.18', array(1.18)),
        array(' string  ,foo', array(' string  ', 'foo')),
    );
  }

  /**
   * @dataProvider searchIndexProvider()
   *
   * @param       $expected
   * @param array $array
   * @param mixed $value
   */
  public function testSearchIndex($expected, $array, $value)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->searchIndex($value));
  }

  /**
   * @return array
   */
  public function searchIndexProvider()
  {
    return array(
        array(false, array(null), ''),
        array(false, array(false), true),
        array(0, array(false), false),
        array(0, array(true), true),
        array(2, array(-9, 1, 0, false), -0),
        array(0, array(1.18), 1.18),
        array(1, array('string', 'foo'), 'foo'),
    );
  }

  /**
   * @dataProvider searchValueProvider()
   *
   * @param       $expected
   * @param array $array
   * @param mixed $value
   */
  public function testSearchValue($expected, $array, $value)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->searchValue($value)->getArray());
  }

  /**
   * @return array
   */
  public function searchValueProvider()
  {
    return array(
        array(array(), array(null), ''),
        array(array(), array(false), 1),
        array(array(0 => false), array(false), 0),
        array(array(true), array(true), 0),
        array(array(0 => 1), array(-9, 1, 0, false), 1),
        array(array(1.18), array(1.18), 0),
        array(array('foo'), array('string', 'foo'), 1),
    );
  }

  public function testMatchesSimple()
  {
    /** @noinspection PhpUnusedParameterInspection */
    /**
     * @param $value
     * @param $key
     *
     * @return bool
     */
    $closure = function ($value, $key) {
      return ($value % 2 === 0);
    };

    $testArray = array(2, 4, 8);
    $result = A::create($testArray)->matches($closure);
    self::assertEquals(true, $result);

    $testArray = array(2, 3, 8);
    $result = A::create($testArray)->matches($closure);
    self::assertEquals(false, $result);
  }

  /**
   * @dataProvider matchesProvider()
   *
   * @param $array
   * @param $search
   * @param $result
   */
  public function testMatches($array, $search, $result)
  {
    $arrayy = A::create($array);

    $closure = function ($a) use ($search) {
      return in_array($a, $search, true);
    };

    $resultMatch = $arrayy->matches($closure);

    self::assertEquals($result, $resultMatch);
  }

  /**
   * @return array
   */
  public function matchesProvider()
  {
    return array(
        array(array(), array(null), true),
        array(array(), array(false), true),
        array(array(0 => false), array(false), true),
        array(array(0 => true), array(true), true),
        array(array(0 => -9), array(-9, 1, 0, false), true),
        array(array(0 => -9, 1, 2), array(-9, 1, 0, false), false),
        array(array(1.18), array(1.18), true),
        array(array('string', 'foo', 'lall'), array('string', 'foo'), false),
    );
  }

  public function testMatchesAnySimple()
  {
    /** @noinspection PhpUnusedParameterInspection */
    /**
     * @param $value
     * @param $key
     *
     * @return bool
     */
    $closure = function ($value, $key) {
      return ($value % 2 === 0);
    };

    $testArray = array(1, 4, 7);
    $result = A::create($testArray)->matchesAny($closure);
    self::assertEquals(true, $result);

    $testArray = array(1, 3, 7);
    $result = A::create($testArray)->matchesAny($closure);
    self::assertEquals(false, $result);
  }

  /**
   * @dataProvider matchesAnyProvider()
   *
   * @param $array
   * @param $search
   * @param $result
   */
  public function testMatchesAny($array, $search, $result)
  {
    $arrayy = A::create($array);

    $closure = function ($a) use ($search) {
      return in_array($a, $search, true);
    };

    $resultMatch = $arrayy->matchesAny($closure);

    self::assertEquals($result, $resultMatch);
  }

  /**
   * @return array
   */
  public function matchesAnyProvider()
  {
    return array(
        array(array(), array(null), true),
        array(array(), array(false), true),
        array(array(0 => false), array(false), true),
        array(array(0 => true), array(true), true),
        array(array(0 => -9), array(-9, 1, 0, false), true),
        array(array(0 => -9, 1, 2), array(-9, 1, 0, false), true),
        array(array(1.18), array(1.18), true),
        array(array('string', 'foo', 'lall'), array('string', 'foo'), true),
    );
  }

  /**
   * @dataProvider containsProvider()
   *
   * @param array $array
   * @param mixed $value
   * @param       $expected
   */
  public function testContains($array, $value, $expected)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->contains($value));
  }

  /**
   * @return array
   */
  public function containsProvider()
  {
    return array(
        array(array(), null, false),
        array(array(), false, false),
        array(array(0 => false), false, true),
        array(array(0 => true), true, true),
        array(array(0 => -9), -9, true),
        array(array(1.18), 1.18, true),
        array(array(1.18), 1.17, false),
        array(array('string', 'foo'), 'foo', true),
        array(array('string', 'foo123'), 'foo', false),
    );
  }

  /**
   * @dataProvider averageProvider()
   *
   * @param array $array
   * @param mixed $value
   * @param       $expected
   */
  public function testAverage($array, $value, $expected)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->average($value));
  }

  /**
   * @return array
   */
  public function averageProvider()
  {
    return array(
        array(array(), null, 0),
        array(array(), 0, 0),
        array(array(0 => false), false, 0),
        array(array(0 => true), true, 1),
        array(array(0 => -9, -8, -7), 1, -8),
        array(array(0 => -9, -8, -7, 1.32), 2, -5.67),
        array(array(1.18), 1, 1.2),
        array(array(1.18, 1.89), 1, 1.5),
        array(array('string', 'foo'), 1, 0),
        array(array('string', 'foo123'), 'foo', 0),
    );
  }

  /**
   * @dataProvider countProvider()
   *
   * @param array $array
   * @param       $expected
   */
  public function testCount($array, $expected)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->count());
    self::assertEquals($expected, $arrayy->size());
    self::assertEquals($expected, $arrayy->length());
  }

  /**
   * @return array
   */
  public function countProvider()
  {
    return array(
        array(array(), 0),
        array(array(null), 1),
        array(array(0 => false), 1),
        array(array(0 => true), 1),
        array(array(0 => -9, -8, -7), 3),
        array(array(0 => -9, -8, -7, 1.32), 4),
        array(array(1.18), 1),
        array(array(1.18, 1.89), 2),
        array(array('string', 'foo'), 2),
        array(array('string', 'foo123'), 2),
    );
  }

  /**
   * @dataProvider maxProvider()
   *
   * @param array $array
   * @param       $expected
   */
  public function testMax($array, $expected)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->max());
  }

  /**
   * @return array
   */
  public function maxProvider()
  {
    return array(
        array(array(), 0),
        array(array(null), null),
        array(array(0 => false), false),
        array(array(0 => true), 1),
        array(array(0 => -9, -8, -7), -7),
        array(array(0 => -9, -8, -7, 1.32), 1.32),
        array(array(1.18), 1.18),
        array(array(1.18, 1.89), 1.89),
        array(array('string', 'foo'), 'string'),
        array(array('string', 'zoom'), 'zoom'),
    );
  }

  /**
   * @dataProvider minProvider()
   *
   * @param array $array
   * @param       $expected
   */
  public function testMin($array, $expected)
  {
    $arrayy = new A($array);

    self::assertEquals($expected, $arrayy->min());
  }

  /**
   * @return array
   */
  public function minProvider()
  {
    return array(
        array(array(), 0),
        array(array(null), null),
        array(array(0 => false), false),
        array(array(0 => true), 1),
        array(array(0 => -9, -8, -7), -9),
        array(array(0 => -9, -8, -7, 1.32), -9),
        array(array(1.18), 1.18),
        array(array(1.18, 1.89), 1.18),
        array(array('string', 'foo'), 'foo'),
        array(array('string', 'zoom'), 'string'),
    );
  }

  /**
   * @dataProvider findProvider()
   *
   * @param $array
   * @param $search
   * @param $result
   */
  public function testFind($array, $search, $result)
  {
    $closure = function ($value) use ($search) {
      return $value === $search;
    };

    $arrayy = A::create($array);
    $resultMatch = $arrayy->find($closure);

    self::assertEquals($result, $resultMatch);
  }

  /**
   * @return array
   */
  public function findProvider()
  {
    return array(
        array(array(), array(null), false),
        array(array(), array(false), false),
        array(array(0 => true), true, true),
        array(array(0 => -9), -9, true),
        array(array(0 => -9, 1, 2), false, false),
        array(array(1.18), 1.18, true),
        array(array('string', 'foo', 'lall'), 'foo', 'foo'),
    );
  }

  /**
   * @dataProvider cleanProvider()
   *
   * @param $array
   * @param $result
   */
  public function testClean($array, $result)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->clean()->getArray());
  }

  /**
   * @return array
   */
  public function cleanProvider()
  {
    return array(
        array(array(), array()),
        array(array(null, false), array()),
        array(array(0 => true), array(0 => true)),
        array(array(0 => -9, 0), array(true)),
        array(array(-8 => -9, 1, 2 => false), array(0 => 1, -8 => -9)),
        array(array(1.18, false), array(true)),
        array(array('foo' => false, 'foo', 'lall'), array('foo', 'lall')),
    );
  }

  /**
   * @return array
   */
  public function simpleArrayProvider()
  {
    return array(
        'empty_array'   => array(
            array(),
            self::TYPE_EMPTY,
        ),
        'indexed_array' => array(
            array(
                1 => 'one',
                2 => 'two',
                3 => 'three',
            ),
            self::TYPE_NUMERIC,
        ),
        'assoc_array'   => array(
            array(
                'one'   => 1,
                'two'   => 2,
                'three' => 3,
            ),
            self::TYPE_ASSOC,
        ),
        'mixed_array'   => array(
            array(
                1     => 'one',
                'two' => 2,
                3     => 'three',
            ),
            self::TYPE_MIXED,
        ),
    );
  }

  public function testSimpleRandomWeighted()
  {
    $testArray = array('foo', 'bar');
    $result = A::create($testArray)->randomWeighted(array('bar' => 2));
    self::assertEquals(1, count($result));

    $testArray = array('foo', 'bar', 'foobar');
    $result = A::create($testArray)->randomWeighted(array('foobar' => 3), 2);
    self::assertEquals(2, count($result));
  }

  /**
   * @dataProvider randomWeightedProvider()
   *
   * @param array $array
   * @param bool  $take
   */
  public function testRandomWeighted($array, $take = null)
  {
    $arrayy = A::create($array);
    $result = $arrayy->randomWeighted(array(0), $take)->getArray();

    self::assertEquals(true, in_array($result[0], $array, true));
  }

  /**
   * @return array
   */
  public function randomWeightedProvider()
  {
    return array(
        array(array(0 => true)),
        array(array(0 => -9, 0)),
        array(array(-8 => -9, 1, 2 => false)),
        array(array(-8 => -9, 1, 2 => false), 2),
        array(array(1.18, false)),
        array(array('foo' => false, 'foo', 'lall')),
        array(array('foo' => false, 'foo', 'lall'), 1),
        array(array('foo' => false, 'foo', 'lall'), 3),
    );
  }

  public function testSimpleRandom()
  {
    $testArray = array(-8 => -9, 1, 2 => false);
    $result = A::create($testArray)->random(3);
    self::assertEquals(3, count($result));

    $testArray = array(-8 => -9, 1, 2 => false);
    $result = A::create($testArray)->random();
    self::assertEquals(1, count($result));
  }

  /**
   * @dataProvider randomProvider()
   *
   * @param array $array
   * @param bool  $take
   */
  public function testRandom($array, $take = null)
  {
    $arrayy = A::create($array);
    $result = $arrayy->random($take)->getArray();

    self::assertEquals(true, in_array($result[0], $array, true));
  }

  /**
   * @return array
   */
  public function randomProvider()
  {
    return array(
        array(array(0 => true)),
        array(array(0 => -9, 0)),
        array(array(-8 => -9, 1, 2 => false)),
        array(array(-8 => -9, 1, 2 => false), 2),
        array(array(1.18, false)),
        array(array('foo' => false, 'foo', 'lall')),
        array(array('foo' => false, 'foo', 'lall'), 1),
        array(array('foo' => false, 'foo', 'lall'), 3),
    );
  }

  /**
   * @dataProvider isAssocProvider()
   *
   * @param array $array
   * @param bool  $result
   */
  public function testIsAssoc($array, $result)
  {
    $resultTmp = A::create($array)->isAssoc();

    self::assertEquals($result, $resultTmp);
  }

  /**
   * @return array
   */
  public function isAssocProvider()
  {
    return array(
        array(array(), false),
        array(array(0 => true), false),
        array(array(0 => -9, 0), false),
        array(array(-8 => -9, 1, 2 => false), false),
        array(array(-8 => -9, 1, 2 => false), false),
        array(array(1.18, false), false),
        array(array(0 => 1, 1 => 2, 2 => 3, 3 => 4), false),
        array(array(1, 2, 3, 4), false),
        array(array(0, 1, 2, 3), false),
        array(array('foo' => false, 'foo1' => 'lall'), true),
    );
  }

  /**
   * @dataProvider isMultiArrayProvider()
   *
   * @param array $array
   * @param bool  $result
   */
  public function testisMultiArray($array, $result)
  {
    $resultTmp = A::create($array)->isMultiArray();

    self::assertEquals($result, $resultTmp);
  }

  /**
   * @return array
   */
  public function isMultiArrayProvider()
  {
    return array(
        array(array(0 => true), false),
        array(array(0 => -9, 0), false),
        array(array(-8 => -9, 1, 2 => false), false),
        array(array(-8 => -9, 1, 2 => false), false),
        array(array(1.18, false), false),
        array(array(0 => 1, 1 => 2, 2 => 3, 3 => 4), false),
        array(array(1, 2, 3, 4), false),
        array(array(0, 1, 2, 3), false),
        array(array('foo' => false, 'foo', 'lall'), false),
        array(array('foo' => false, 'foo', 'lall'), false),
        array(array('foo' => false, 'foo', 'lall'), false),
        array(array('foo' => array('foo', 'lall')), true),
        array(array('foo' => array('foo', 'lall'), 'bar' => array('foo', 'lall')), true),
    );
  }

  public function testSplit()
  {
    self::assertArrayy(A::create()->split());

    self::assertEquals(
        A::create(array(array('a'), array('b'))),
        A::create(array('a', 'b'))->split()
    );

    self::assertEquals(
        A::create(array(array('a' => 1), array('b' => 2))),
        A::create(array('a' => 1, 'b' => 2))->split(2, true)
    );

    self::assertEquals(
        A::create(
            array(
                0 => array(
                    0 => 1,
                    1 => 2,
                ),
                1 => array(
                    0 => 3,
                ),
            )
        ),
        A::create(
            array(
                'a' => 1,
                'b' => 2,
                'c' => 3,
            )
        )->split(2, false)
    );
  }

  public function testColumn()
  {
    $rows = array(0 => array('id' => '3', 'title' => 'Foo', 'date' => '2013-03-25'));

    self::assertEquals(A::create($rows), A::create($rows)->getColumn(null, 0));
    self::assertEquals(A::create($rows), A::create($rows)->getColumn(null));
    self::assertEquals(A::create($rows), A::create($rows)->getColumn());

    $expected = array(
        0 => '3',
    );
    self::assertEquals(A::create($expected), A::create($rows)->getColumn('id'));

    // ---

    $rows = array(
        456 => array('id' => '3', 'title' => 'Foo', 'date' => '2013-03-25'),
        457 => array('id' => '5', 'title' => 'Bar', 'date' => '2012-05-20'),
    );

    $expected = array(
        3 => 'Foo',
        5 => 'Bar',
    );
    self::assertEquals(A::create($expected), A::create($rows)->getColumn('title', 'id'));

    $expected = array(
        0 => 'Foo',
        1 => 'Bar',
    );
    self::assertEquals(A::create($expected), A::create($rows)->getColumn('title', null));


    // pass null as second parameter to get back all columns indexed by third parameter
    $expected1 = array(
        3 => array('id' => '3', 'title' => 'Foo', 'date' => '2013-03-25'),
        5 => array('id' => '5', 'title' => 'Bar', 'date' => '2012-05-20'),
    );
    self::assertEquals(A::create($expected1), A::create($rows)->getColumn(null, 'id'));

    // pass null as second parameter and bogus third param to get back zero-indexed array of all columns
    $expected2 = array(
        array('id' => '3', 'title' => 'Foo', 'date' => '2013-03-25'),
        array('id' => '5', 'title' => 'Bar', 'date' => '2012-05-20'),
    );
    self::assertEquals(A::create($expected2), A::create($rows)->getColumn(null, 'foo'));

    // pass null as second parameter and no third param to get back array_values(input) (same as $expected2)
    self::assertEquals(A::create($expected2), A::create($rows)->getColumn(null));
  }

  public function testCanGetIntersectionOfTwoArrays()
  {
    $a = array('foo', 'bar');
    $b = array('bar', 'baz');
    $array = A::create($a)->intersection($b);
    self::assertEquals(array('bar'), $array->getArray());
  }

  public function testIntersectsBooleanFlag()
  {
    $a = array('foo', 'bar');
    $b = array('bar', 'baz');
    self::assertTrue(A::create($a)->intersects($b));

    $a = 'bar';
    self::assertTrue(A::create($a)->intersects($b));

    $a = 'foo';
    self::assertFalse(A::create($a)->intersects($b));
  }

  /**
   * @dataProvider firstsProvider()
   *
   * @param array $array
   * @param array $result
   * @param null  $take
   */
  public function testFirsts($array, $result, $take = null)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->firsts($take)->getArray());
  }

  /**
   * @return array
   */
  public function firstsProvider()
  {
    return array(
        array(array(), array()),
        array(array(null, false), array()),
        array(array(0 => true), array(true)),
        array(array(0 => -9, 0), array(-9)),
        array(array(-8 => -9, 1, 2 => false), array(-9)),
        array(array(1.18, false), array(1.18)),
        array(array('foo' => false, 'foo', 'lall'), array(false)),
        array(array(-8 => -9, 1, 2 => false), array(), 0),
        array(array(1.18, false), array(1.18), 1),
        array(array('foo' => false, 'foo', 'lall'), array('foo', 'foo' => false), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'foo', 1 => 'bar'), 2),
    );
  }


  /**
   * @dataProvider firstProvider()
   *
   * @param array $array
   * @param array $result
   */
  public function testFirst($array, $result)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->first());
  }

  /**
   * @return array
   */
  public function firstProvider()
  {
    return array(
        array(array(), null),
        array(array(null, false), null),
        array(array(0 => true), true),
        array(array(0 => -9, 0), -9),
        array(array(-8 => -9, 1, 2 => false), -9),
        array(array(1.18, false), 1.18),
        array(array('foo' => false, 'foo', 'lall'), false),
        array(array(-8 => -9, 1, 2 => false), -9),
        array(array(1.18, false), 1.18),
        array(array('foo' => false, 'foo', 'lall'), false),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), 'foo'),
    );
  }

  /**
   * @dataProvider lastProvider()
   *
   * @param array $array
   * @param array $result
   * @param null  $take
   */
  public function testLast($array, $result, $take = null)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->lasts($take)->getArray());
  }

  /**
   * @return array
   */
  public function lastProvider()
  {
    return array(
        array(array(), array()),
        array(array(null, false), array(false)),
        array(array(0 => true), array(true)),
        array(array(0 => -9, 0), array(0)),
        array(array(-8 => -9, 1, 2 => false), array(false)),
        array(array(1.18, false), array(false)),
        array(array('foo' => false, 'foo', 'lall'), array('lall')),
        array(array(-8 => -9, 1, 2 => false), array(-9, 1, false), 0),
        array(array(1.18, false), array(false), 1),
        array(array('foo' => false, 'foo', 'lall'), array('foo', 'lall'), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'bar', 1 => 'lall'), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'lall')),
    );
  }

  /**
   * @dataProvider initialProvider()
   *
   * @param array $array
   * @param array $result
   * @param int   $to
   */
  public function testInitial($array, $result, $to = 1)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->initial($to)->getArray());
  }

  /**
   * @return array
   */
  public function initialProvider()
  {
    return array(
        array(array(), array()),
        array(array(null, false), array(null)),
        array(array(0 => true), array()),
        array(array(0 => -9, 0), array(-9)),
        array(array(-8 => -9, 1, 2 => false), array(-9, 1)),
        array(array(1.18, false), array(1.18)),
        array(array('foo' => false, 'foo', 'lall'), array('foo' => false, 0 => 'foo')),
        array(array(-8 => -9, 1, 2 => false), array(0 => -9, 1 => 1, 2 => false), 0),
        array(array(1.18, false), array(1.18), 1),
        array(array('foo' => false, 'foo', 'lall'), array('foo' => false), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'foo'), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'foo', 1 => 'bar'), 1),
    );
  }

  /**
   * @dataProvider restProvider()
   *
   * @param array $array
   * @param array $result
   * @param int   $from
   */
  public function testRest($array, $result, $from = 1)
  {
    $arrayy = A::create($array);

    self::assertEquals($result, $arrayy->rest($from)->getArray());
  }

  /**
   * @return array
   */
  public function restProvider()
  {
    return array(
        array(array(), array()),
        array(array(null, false), array(null)),
        array(array(0 => true), array()),
        array(array(0 => -9, 0), array(0)),
        array(array(-8 => -9, 1, 2 => false), array(0 => 1, 1 => false)),
        array(array(1.18, false), array(false)),
        array(array('foo' => false, 'foo', 'lall'), array(0 => 'foo', 1 => 'lall')),
        array(array(-8 => -9, 1, 2 => false), array(0 => -9, 1 => 1, 2 => false), 0),
        array(array(1.18, false), array(false), 1),
        array(array('foo' => false, 'foo', 'lall'), array('lall'), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'lall'), 2),
        array(array(2 => 'foo', 3 => 'bar', 4 => 'lall'), array(0 => 'bar', 1 => 'lall'), 1),
    );
  }

  public function testCanDoSomethingAtEachValue()
  {
    $arrayy = A::create(array('foo', 'bar' => 'bis'));

    $closure = function ($value, $key) {
      echo $key . ':' . $value . ':';
    };

    $arrayy->at($closure);
    $result = '0:foo:bar:bis:';
    $this->expectOutputString($result);
  }

  public function testSimpleAt()
  {
    $result = A::create();
    $closure = function ($value, $key) use ($result) {
      $result[$key] = ':' . $value . ':';
    };

    A::create(array('foo', 'bar' => 'bis'))->at($closure);
    self::assertEquals(A::create(array(':foo:', 'bar' => ':bis:')), $result);
  }

  public function testReplaceOneValue()
  {
    $testArray = array('bar', 'foo' => 'foo', 'foobar' => 'foobar');
    $arrayy = A::create($testArray)->replaceOneValue('foo', 'replaced');
    self::assertEquals('replaced', $arrayy['foo']);
    self::assertEquals('foobar', $arrayy['foobar']);
  }

  public function testReplaceValues()
  {
    $testArray = array('bar', 'foo' => 'foo', 'foobar' => 'foobar');
    $arrayy = A::create($testArray)->replaceValues('foo', 'replaced');
    self::assertEquals('replaced', $arrayy['foo']);
    self::assertEquals('replacedbar', $arrayy['foobar']);
  }

  public function testReplaceKeys()
  {
    $arrayy = A::create(array(1 => 'bar', 'foo' => 'foo'))->replaceKeys(array(1 => 2, 'foo' => 'replaced'));
    self::assertEquals('bar', $arrayy[2]);
    self::assertEquals('foo', $arrayy['replaced']);

    $arrayy = A::create(array(1 => 'bar', 'foo' => 'foo'))->replaceKeys(array(1, 'foo' => 'replaced'));
    self::assertEquals('bar', $arrayy[1]);
    self::assertEquals('foo', $arrayy['replaced']);
  }

  public function testEach()
  {
    $arrayy = A::create(array(1 => 'bar', 'foo' => 'foo'));

    $closure = function ($value, $key) {
      return $key . ':' . $value;
    };

    $under = $arrayy->each($closure);
    $result = array('foo' => 'foo:foo', 1 => '1:bar');
    self::assertEquals($result, $under->getArray());
  }

  public function testSimpleEach()
  {
    $closure = function ($value) {
      return ':' . $value . ':';
    };

    $result = A::create(array('foo', 'bar' => 'bis'))->each($closure);
    self::assertEquals(array(':foo:', 'bar' => ':bis:'), $result->getArray());
  }

  public function testShuffle()
  {
    $arrayy = A::create(array(1 => 'bar', 'foo' => 'foo'))->shuffle();

    self::assertEquals(true, in_array('bar', $arrayy->getArray(), true));
    self::assertEquals(true, in_array('foo', $arrayy->getArray(), true));
  }

  /**
   * @dataProvider sortKeysProvider()
   *
   * @param $array
   * @param $result
   * @param $direction
   */
  public function testSortKeys($array, $result, $direction = 'ASC')
  {
    $arrayy = A::create($array)->sortKeys($direction);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function sortKeysProvider()
  {
    return array(
        array(array(), array()),
        array(array(), array()),
        array(array(0 => false), array(false)),
        array(array(0 => true), array(true)),
        array(array(0 => -9), array(-9), 'ASC'),
        array(array(0 => -9, 1, 2), array(-9, 1, 2), 'asc'),
        array(array(1 => 2, 0 => 1), array(1, 2), 'asc'),
        array(array(1.18), array(1.18), 'ASC'),
        array(array(3 => 'string', 'foo', 'lall'), array(5 => 'lall', 4 => 'foo', 3 => 'string'), 'desc'),
    );
  }

  /**
   * @dataProvider implodeProvider()
   *
   * @param $array
   * @param $result
   * @param $with
   */
  public function testImplode($array, $result, $with = ',')
  {
    $string = A::create($array)->implode($with);

    self::assertEquals($result, $string);
  }

  /**
   * @return array
   */
  public function implodeProvider()
  {
    return array(
        array(array(), ''),
        array(array(), ''),
        array(array(0 => false), ''),
        array(array(0 => true), '1'),
        array(array(0 => -9), '-9', '|'),
        array(array(0 => -9, 1, 2), '-9|1|2', '|'),
        array(array(1.18), '1.18'),
        array(array(3 => 'string', 'foo', 'lall'), 'string,foo,lall', ','),
    );
  }

  public function testFilter()
  {
    $under = A::create(array(1, 2, 3, 4))->filter(
        function ($value) {
          return $value % 2 !== 0;
        }
    );
    self::assertEquals(array(0 => 1, 2 => 3), $under->getArray());

    $under = A::create(array(1, 2, 3, 4))->filter();
    self::assertEquals(array(1, 2, 3, 4), $under->getArray());
  }

  public function testInvoke()
  {
    $array = array('   foo  ', '   bar   ');
    $arrayy = A::create($array)->invoke('trim');
    self::assertEquals(array('foo', 'bar'), $arrayy->getArray());

    $array = array('_____foo', '____bar   ');
    $arrayy = A::create($array)->invoke('trim', ' _');
    self::assertEquals(array('foo', 'bar'), $arrayy->getArray());

    $array = array('_____foo  ', '__bar   ');
    $arrayy = A::create($array)->invoke('trim', array('_', ' '));
    self::assertEquals(array('foo  ', '__bar'), $arrayy->getArray());
  }

  public function testReject()
  {
    $array = array(1, 2, 3, 4);
    $arrayy = A::create($array)->reject(
        function ($value) {
          return $value % 2 !== 0;
        }
    );
    self::assertEquals(array(1 => 2, 3 => 4), $arrayy->getArray());
  }

  /**
   * @dataProvider hasProvider()
   *
   * @param mixed $expected
   * @param array $array
   * @param mixed $key
   */
  public function testHas($expected, $array, $key)
  {
    $arrayy = new A($array);
    self::assertEquals($expected, $arrayy->has($key));
  }

  /**
   * @return array
   */
  public function hasProvider()
  {
    return array(
        array(false, array(null), 0),
        array(true, array(false), 0),
        array(false, array(true), 1),
        array(false, array(false), 1),
        array(true, array(true), 0),
        array(true, array(-9, 1, 0, false), 1),
        array(true, array(1.18), 0),
        array(false, array(' string  ', 'foo'), 'foo'),
        array(true, array(' string  ', 'foo' => 'foo'), 'foo'),
    );
  }

  /**
   * @dataProvider getProvider()
   *
   * @param mixed $expected
   * @param array $array
   * @param mixed $key
   */
  public function testGetV2($expected, $array, $key)
  {
    $arrayy = new A($array);
    self::assertEquals($expected, $arrayy->get($key));
  }

  /**
   * @return array
   */
  public function getProvider()
  {
    return array(
        array(null, array(null), 0),
        array(false, array(false), 0),
        array(null, array(true), 1),
        array(null, array(false), 1),
        array(true, array(true), 0),
        array(1, array(-9, 1, 0, false), 1),
        array(1.18, array(1.18), 0),
        array(false, array(' string  ', 'foo'), 'foo'),
        array('foo', array(' string  ', 'foo' => 'foo'), 'foo'),
    );
  }

  /**
   * @dataProvider setProvider()
   *
   * @param array $array
   * @param mixed $key
   * @param mixed $value
   */
  public function testSet($array, $key, $value)
  {
    $arrayy = new A($array);
    $arrayy = $arrayy->set($key, $value)->getArray();
    self::assertEquals($value, $arrayy[$key]);
  }

  /**
   * @return array
   */
  public function setProvider()
  {
    return array(
        array(array(null), 0, 'foo'),
        array(array(false), 0, true),
        array(array(true), 1, 'foo'),
        array(array(false), 1, 'foo'),
        array(array(true), 0, 'foo'),
        array(array(-9, 1, 0, false), 1, 'foo'),
        array(array(1.18), 0, 1),
        array(array(' string  ', 'foo'), 'foo', 'lall'),
        array(array(' string  ', 'foo' => 'foo'), 'foo', 'lall'),
    );
  }

  /**
   * @dataProvider setAndGetProvider()
   *
   * @param array $array
   * @param mixed $key
   * @param mixed $value
   */
  public function testSetAndGet($array, $key, $value)
  {
    $arrayy = new A($array);
    $result = $arrayy->setAndGet($key, $value);
    self::assertEquals($value, $result);
  }

  /**
   * @return array
   */
  public function setAndGetProvider()
  {
    return array(
        array(array(null), 0, 'foo'),
        array(array(false), 0, false),
        array(array(true), 1, 'foo'),
        array(array(false), 1, 'foo'),
        array(array(true), 0, true),
        array(array(-9, 1, 0, false), 1, 1),
        array(array(1.18), 0, 1.18),
        array(array(' string  ', 'foo'), 'foo', 'lall'),
        array(array(' string  ', 'foo' => 'foo'), 'foo', 'foo'),
    );
  }

  /**
   * @dataProvider removeProvider()
   *
   * @param array $array
   * @param mixed $key
   * @param array $result
   */
  public function testRemove($array, $key, $result)
  {
    $arrayy = new A($array);
    $resultTmp = $arrayy->remove($key)->getArray();
    self::assertEquals($result, $resultTmp);
  }

  /**
   * @return array
   */
  public function removeProvider()
  {
    return array(
        array(array(null), 0, array()),
        array(array(false), 0, array()),
        array(array(true), 1, array(true)),
        array(array(false), 1, array(false)),
        array(array(true), 0, array()),
        array(array(-9, 1, 0, false), 1, array(0 => -9, 2 => 0, 3 => false)),
        array(array(1.18), 0, array()),
        array(array(' string  ', 'foo'), 'foo', array(' string  ', 'foo')),
        array(array(' string  ', 'foo' => 'foo'), 'foo', array(' string  ')),
    );
  }

  public function testFilterBy()
  {
    $a = array(
        array('id' => 123, 'name' => 'foo', 'group' => 'primary', 'value' => 123456, 'when' => '2014-01-01'),
        array('id' => 456, 'name' => 'bar', 'group' => 'primary', 'value' => 1468, 'when' => '2014-07-15'),
        array('id' => 499, 'name' => 'baz', 'group' => 'secondary', 'value' => 2365, 'when' => '2014-08-23'),
        array('id' => 789, 'name' => 'ter', 'group' => 'primary', 'value' => 2468, 'when' => '2010-03-01'),
        array('id' => 888, 'name' => 'qux', 'value' => 6868, 'when' => '2015-01-01'),
        array('id' => 999, 'name' => 'flux', 'group' => null, 'value' => 6868, 'when' => '2015-01-01'),
    );

    $arrayy = new A($a);

    $b = $arrayy->filterBy('name', 'baz');
    self::assertCount(1, $b);
    /** @noinspection OffsetOperationsInspection */
    self::assertEquals(2365, $b[0]['value']);

    $b = $arrayy->filterBy('name', array('baz'));
    self::assertCount(1, $b);
    /** @noinspection OffsetOperationsInspection */
    self::assertEquals(2365, $b[0]['value']);

    $c = $arrayy->filterBy('value', 2468);
    self::assertCount(1, $c);
    /** @noinspection OffsetOperationsInspection */
    self::assertEquals('primary', $c[0]['group']);

    $d = $arrayy->filterBy('group', 'primary');
    self::assertCount(3, $d);

    $e = $arrayy->filterBy('value', 2000, 'lt');
    self::assertCount(1, $e);
    /** @noinspection OffsetOperationsInspection */
    self::assertEquals(1468, $e[0]['value']);

    $e = $arrayy->filterBy('value', array(2468, 2365), 'contains');
    self::assertCount(2, $e);
  }

  public function testReplace()
  {
    $arrayyTmp = A::create(array(1 => 'foo', 2 => 'foo2', 3 => 'bar'));
    $arrayy = $arrayyTmp->replace(1, 'notfoo', 'notbar');

    $matcher = array(
        'notfoo' => 'notbar',
        2        => 'foo2',
        3        => 'bar',
    );
    self::assertEquals($matcher, $arrayy->getArray());
  }

  public function testKeys()
  {
    $arrayyTmp = A::create(array(1 => 'foo', 2 => 'foo2', 3 => 'bar'));
    $keys = $arrayyTmp->keys();

    $matcher = array(1, 2, 3,);
    self::assertEquals($matcher, $keys->getArray());
  }

  public function testValues()
  {
    $arrayyTmp = A::create(array(1 => 'foo', 2 => 'foo2', 3 => 'bar'));
    $values = $arrayyTmp->values();

    $matcher = array(0 => 'foo', 1 => 'foo2', 2 => 'bar');
    self::assertEquals($matcher, $values->getArray());
  }

  public function testSort()
  {
    $testArray = array(5, 3, 1, 2, 4);
    $under = A::create($testArray)->sorter(null, 'desc');
    self::assertEquals(array(5, 4, 3, 2, 1), $under->getArray());

    $testArray = range(1, 5);
    $under = A::create($testArray)->sorter(
        function ($value) {
          if ($value % 2 === 0) {
            return -1;
          } else {
            return 1;
          }
        }
    );
    self::assertEquals(array(2, 4, 1, 3, 5), $under->getArray());
  }

  public function testCanGroupValues()
  {
    $under = A::create(range(1, 5))->group(
        function ($value) {
          return $value % 2 === 0;
        }
    );
    $matcher = array(
        array(1, 3, 5),
        array(2, 4),
    );
    self::assertEquals($matcher, $under->getArray());
  }

  public function testCanGroupValuesWithSavingKeys()
  {
    $grouper = function ($value) {
      return $value % 2 === 0;
    };
    $under = A::create(range(1, 5))->group($grouper, true);
    $matcher = array(
        array(0 => 1, 2 => 3, 4 => 5),
        array(1 => 2, 3 => 4),
    );
    self::assertEquals($matcher, $under->getArray());
  }

  public function testCanGroupValuesWithNonExistingKey()
  {
    self::assertEquals(array(), A::create(range(1, 5))->group('unknown', true)->getArray());
    self::assertEquals(array(), A::create(range(1, 5))->group('unknown', false)->getArray());
  }

  public function testCanIndexBy()
  {
    $array = array(
        array('name' => 'moe', 'age' => 40),
        array('name' => 'larry', 'age' => 50),
        array('name' => 'curly', 'age' => 60),
    );
    $expected = array(
        40 => array('name' => 'moe', 'age' => 40),
        50 => array('name' => 'larry', 'age' => 50),
        60 => array('name' => 'curly', 'age' => 60),
    );
    self::assertEquals($expected, A::create($array)->indexBy('age')->getArray());
  }

  public function testIndexByReturnSome()
  {
    $array = array(
        array('name' => 'moe', 'age' => 40),
        array('name' => 'larry', 'age' => 50),
        array('name' => 'curly'),
    );
    $expected = array(
        40 => array('name' => 'moe', 'age' => 40),
        50 => array('name' => 'larry', 'age' => 50),
    );
    self::assertEquals($expected, A::create($array)->indexBy('age')->getArray());
  }

  public function testIndexByReturnEmpty()
  {
    $array = array(
        array('name' => 'moe', 'age' => 40),
        array('name' => 'larry', 'age' => 50),
        array('name' => 'curly'),
    );
    self::assertEquals(array(), A::create($array)->indexBy('vaaaa')->getArray());
  }

  /**
   * @dataProvider removeV2Provider()
   *
   * @param $array
   * @param $result
   * @param $key
   */
  public function testRemoveV2($array, $result, $key)
  {
    $arrayy = A::create($array)->remove($key);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function removeV2Provider()
  {
    return array(
        array(array(), array(), null),
        array(array(0 => false), array(0 => false), false),
        array(array(0 => true), array(0 => true), false),
        array(array(0 => -9), array(0 => -9), -1),
        array(array(0 => -9, 1, 2), array(0 => -9, 2 => 2), 1),
        array(array(1.18, 1.5), array(1 => 1.5), 0),
        array(array(3 => 'string', 'foo', 'lall'), array(3 => 'string', 'foo',), 5),
    );
  }

  /**
   * @dataProvider removeFirstProvider()
   *
   * @param $array
   * @param $result
   */
  public function testRemoveFirst($array, $result)
  {
    $arrayy = A::create($array)->removeFirst();

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function removeFirstProvider()
  {
    return array(
        array(array(), array()),
        array(array(0 => false), array()),
        array(array(0 => true), array()),
        array(array(0 => -9), array()),
        array(array(0 => -9, 1, 2), array(1, 2)),
        array(array(1.18, 1.5), array(1.5)),
        array(array(3 => 'string', 'foo', 'lall'), array('foo', 'lall')),
    );
  }

  /**
   * @dataProvider removeLastProvider()
   *
   * @param $array
   * @param $result
   */
  public function testRemoveLast($array, $result)
  {
    $arrayy = A::create($array)->removeLast();

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function removeLastProvider()
  {
    return array(
        array(array(), array()),
        array(array(0 => false), array()),
        array(array(0 => true), array()),
        array(array(0 => -9), array()),
        array(array(0 => -9, 1, 2), array(-9, 1)),
        array(array(1.18, 1.5), array(1.18)),
        array(array(3 => 'string', 'foo', 'lall'), array(3 => 'string', 4 => 'foo')),
    );
  }

  /**
   * @dataProvider removeValueProvider()
   *
   * @param $array
   * @param $result
   * @param $value
   */
  public function testRemoveValue($array, $result, $value)
  {
    $arrayy = A::create($array)->removeValue($value);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function removeValueProvider()
  {
    return array(
        array(array(), array(), ''),
        array(array(0 => false), array(), false),
        array(array(0 => true), array(), true),
        array(array(0 => -9), array(), -9),
        array(array(0 => -9, 1, 2), array(-9, 1), 2),
        array(array(1.18, 1.5), array(1.18), 1.5),
        array(array(3 => 'string', 'foo', 'lall'), array(0 => 'string', 1 => 'foo'), 'lall'),
    );
  }

  /**
   * @dataProvider prependProvider()
   *
   * @param $array
   * @param $result
   * @param $value
   */
  public function testPrepend($array, $result, $value)
  {
    $arrayy = A::create($array)->prepend($value);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function prependProvider()
  {
    return array(
        array(array(), array('foo'), 'foo'),
        array(array(0 => false), array(true, false), true),
        array(array(0 => true), array(false, true), false),
        array(array(0 => -9), array(-6, -9), -6),
        array(array(0 => -9, 1, 2), array(3, -9, 1, 2), 3),
        array(array(1.18, 1.5), array(1.2, 1.18, 1.5), 1.2),
        array(
            array(3 => 'string', 'foo', 'lall'),
            array(
                0 => 'foobar',
                1 => 'string',
                2 => 'foo',
                3 => 'lall',
            ),
            'foobar',
        ),
    );
  }

  /**
   * @dataProvider appendProvider()
   *
   * @param $array
   * @param $result
   * @param $value
   */
  public function testAppend($array, $result, $value)
  {
    $arrayy = A::create($array)->append($value);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function appendProvider()
  {
    return array(
        array(array(), array('foo'), 'foo'),
        array(array(0 => false), array(false, true), true),
        array(array(0 => true), array(true, false), false),
        array(array(0 => -9), array(-9, -6), -6),
        array(array(0 => -9, 1, 2), array(-9, 1, 2, 3), 3),
        array(array(1.18, 1.5), array(1.18, 1.5, 1.2), 1.2),
        array(array('fòô' => 'bàř'), array('fòô' => 'bàř', 0 => 'foo'), 'foo'),
        array(
            array(3 => 'string', 'foo', 'lall'),
            array(
                3 => 'string',
                4 => 'foo',
                5 => 'lall',
                6 => 'foobar',
            ),
            'foobar',
        ),
    );
  }

  /**
   * @dataProvider uniqueProvider()
   *
   * @param $array
   * @param $result
   */
  public function testUnique($array, $result)
  {
    $arrayy = A::create($array)->unique();

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function uniqueProvider()
  {
    return array(
        array(array(), array()),
        array(array(0 => false), array(false)),
        array(array(0 => true), array(true)),
        array(array(0 => -9, -9), array(-9)),
        array(array(0 => -9, 1, 2), array(-9, 1, 2)),
        array(array(1.18, 1.5), array(1.18, 1.5)),
        array(
            array(3 => 'string', 'foo', 'lall', 'foo'),
            array(
                0 => 'string',
                1 => 'foo',
                2 => 'lall',
            ),
        ),
    );
  }

  /**
   * @dataProvider reverseProvider()
   *
   * @param $array
   * @param $result
   */
  public function testReverse($array, $result)
  {
    $arrayy = A::create($array)->reverse();

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function reverseProvider()
  {
    return array(
        array(array(), array()),
        array(array(0 => false), array(false)),
        array(array(0 => true), array(true)),
        array(array(0 => -9, -9), array(0 => -9, 1 => -9)),
        array(array(0 => -9, 1, 2), array(0 => 2, 1 => 1, 2 => -9)),
        array(array(1.18, 1.5), array(1.5, 1.18)),
        array(
            array(3 => 'string', 'foo', 'lall', 'foo'),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
        ),
    );
  }

  /**
   * @dataProvider mergeAppendNewIndexProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testMergeAppendNewIndex($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->mergeAppendNewIndex($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function mergeAppendNewIndexProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array(false, false)),
        array(array(0 => true), array(true), array(true, true)),
        array(
            array(
                0 => -9,
                -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
                2 => -9,
                3 => -9,
            ),
        ),
        array(
            array(
                0 => -9,
                1 => 1,
                2 => 2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(
                0 => -9,
                1 => 1,
                2 => 2,
                3 => 2,
                4 => 1,
                5 => -9,
            ),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(1.18, 1.5, 1.5, 1.18),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                0     => 'one',
                1     => 'two',
                'foo' => 'bar2',
                2     => 'three',
                3     => 'four',
                4     => 'six',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(
                0 => 'string',
                1 => 'foo',
                2 => 'lall',
                3 => 'foo',
                4 => 'foo',
                5 => 'lall',
                6 => 'foo',
                7 => 'string',
            ),
        ),
    );
  }

  /**
   * @dataProvider mergePrependNewIndexProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testMergePrependNewIndex($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->mergePrependNewIndex($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function mergePrependNewIndexProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array(false, false)),
        array(array(0 => true), array(true), array(true, true)),
        array(
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
                2 => -9,
                3 => -9,
            ),
        ),
        array(
            array(
                0 => -9,
                1,
                2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
                3 => -9,
                4 => 1,
                5 => 2,
            ),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(1.5, 1.18, 1.18, 1.5),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                1     => 'four',
                'foo' => 'bar1',
                2     => 'six',
                3     => 'one',
                4     => 'two',
                0     => 'three',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
                4 => 'string',
                5 => 'foo',
                6 => 'lall',
                7 => 'foo',
            ),
        ),
    );
  }

  /**
   * @dataProvider mergeAppendKeepIndexProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testMergeAppendKeepIndex($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->mergeAppendKeepIndex($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function mergePrependKeepIndexProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array(false)),
        array(array(0 => true), array(true), array(true)),
        array(
            array(
                0 => -9,
                -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
        ),
        array(
            array(
                0 => -9,
                1,
                2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(
                0 => -9,
                1 => 1,
                2 => 2,
            ),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(1.18, 1.5),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                1     => 'one',
                'foo' => 'bar1',
                2     => 'two',
                3     => 'three',
                4     => 'four',
                6     => 'six',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
                4 => 'foo',
                5 => 'lall',
                6 => 'foo',
            ),
        ),
    );
  }

  /**
   * @dataProvider mergePrependKeepIndexProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testMergePrependKeepIndex($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->mergePrependKeepIndex($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function mergeAppendKeepIndexProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array(false)),
        array(array(0 => true), array(true), array(true)),
        array(
            array(
                0 => -9,
                -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
        ),
        array(
            array(
                0 => -9,
                1,
                2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(1.5, 1.18),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                1     => 'one',
                'foo' => 'bar2',
                2     => 'two',
                3     => 'three',
                4     => 'four',
                6     => 'six',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
                4 => 'foo',
                5 => 'lall',
                6 => 'foo',
            ),
        ),
    );
  }

  /**
   * @dataProvider diffProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testDiff($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->diff($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function diffProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array()),
        array(array(0 => true), array(true), array()),
        array(
            array(
                0 => -9,
                1 => -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(),
        ),
        array(
            array(
                0 => -9,
                1,
                2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                1     => 'one',
                'foo' => 'bar1',
                2     => 'two',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(),
        ),
    );
  }

  /**
   * @dataProvider diffReverseProvider()
   *
   * @param $array
   * @param $arrayNew
   * @param $result
   */
  public function testdiffReverse($array, $arrayNew, $result)
  {
    $arrayy = A::create($array)->diffReverse($arrayNew);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @return array
   */
  public function diffReverseProvider()
  {
    return array(
        array(array(), array(), array()),
        array(array(0 => false), array(false), array()),
        array(array(0 => true), array(true), array()),
        array(
            array(
                0 => -9,
                -9,
            ),
            array(
                0 => -9,
                1 => -9,
            ),
            array(),
        ),
        array(
            array(
                0 => -9,
                1,
                2,
            ),
            array(
                0 => 2,
                1 => 1,
                2 => -9,
            ),
            array(),
        ),
        array(
            array(1.18, 1.5),
            array(1.5, 1.18),
            array(),
        ),
        array(
            array(
                1     => 'one',
                2     => 'two',
                'foo' => 'bar1',
            ),
            array(
                3     => 'three',
                4     => 'four',
                6     => 'six',
                'foo' => 'bar2',
            ),
            array(
                'foo' => 'bar2',
                3     => 'three',
                4     => 'four',
                6     => 'six',
            ),
        ),
        array(
            array(
                3 => 'string',
                'foo',
                'lall',
                'foo',
            ),
            array(
                0 => 'foo',
                1 => 'lall',
                2 => 'foo',
                3 => 'string',
            ),
            array(),
        ),
    );
  }

  public function testReduce()
  {
    $testArray = array('foo', 2 => 'bar', 4 => 'lall');

    $myReducer = function ($resultArray, $value) {
      if ($value == 'foo') {
        $resultArray[] = $value;
      }

      return $resultArray;
    };

    $arrayy = A::create($testArray)->reduce($myReducer);

    $expected = array('foo');
    self::assertEquals($expected, $arrayy->getArray());
  }

  public function testReduceViaFunction()
  {
    $testArray = array('foo', 2 => 'bar', 4 => 'lall');

    /**
     * @param $resultArray
     * @param $value
     *
     * @return array
     */
    function myReducer($resultArray, $value)
    {
      if ($value == 'foo') {
        $resultArray[] = $value;
      }

      return $resultArray;
    }

    $arrayy = A::create($testArray)->reduce('myReducer');

    $expected = array('foo');
    self::assertEquals($expected, $arrayy->getArray());
  }

  public function testFlip()
  {
    $testArray = array(0 => 'foo', 2 => 'bar', 4 => 'lall');
    $arrayy = A::create($testArray)->flip();

    $expected = array('foo' => 0, 'bar' => 2, 'lall' => 4);
    self::assertEquals($expected, $arrayy->getArray());
  }

  public function testCreateFromJson()
  {
    $str = '
    {"employees":[
      {"firstName":"John", "lastName":"Doe"},
      {"firstName":"Anna", "lastName":"Smith"},
      {"firstName":"Peter", "lastName":"Jones"}
    ]}';

    $arrayy = A::createFromJson($str);

    $expected = array(
        'employees' => array(
            0 => array(
                'firstName' => 'John',
                'lastName'  => 'Doe',
            ),
            1 => array(
                'firstName' => 'Anna',
                'lastName'  => 'Smith',
            ),
            2 => array(
                'firstName' => 'Peter',
                'lastName'  => 'Jones',
            ),
        ),
    );

    // test JSON -> Array
    self::assertEquals($expected, $arrayy->getArray());

    // test Array -> JSON
    self::assertEquals(
        str_replace(array(' ', "\n", "\n\r", "\r"), '', $str),
        $arrayy->toJson()
    );
  }

  public function testCreateFromStringSimple()
  {
    $str = 'John, Doe, Anna, Smith';

    $arrayy = A::createFromString($str, ',');

    $expected = array('John', 'Doe', 'Anna', 'Smith');

    // test String -> Array
    self::assertEquals($expected, $arrayy->getArray());
  }

  public function testCreateFromStringRegEx()
  {
    $str = '
    [2016-03-02 02:37:39] WARN  main : router: error in file-name: jquery.min.map
    [2016-03-02 02:39:07] WARN  main : router: error in file-name: jquery.min.map
    [2016-03-02 02:44:01] WARN  main : router: error in file-name: jquery.min.map
    [2016-03-02 02:45:21] WARN  main : router: error in file-name: jquery.min.map
    ';

    $arrayy = A::createFromString($str, null, '/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*/');

    $expected = array(
        '[2016-03-02 02:37:39] WARN  main : router: error in file-name: jquery.min.map',
        '[2016-03-02 02:39:07] WARN  main : router: error in file-name: jquery.min.map',
        '[2016-03-02 02:44:01] WARN  main : router: error in file-name: jquery.min.map',
        '[2016-03-02 02:45:21] WARN  main : router: error in file-name: jquery.min.map',
    );

    // test String -> Array
    self::assertEquals($expected, $arrayy->getArray());
  }

  public function testOrderByKey()
  {
    $array = array(
        99  => 'aaa',
        100 => 'bcd',
        101 => 123,
        1   => 'Bcde',
        3   => 'bcde',
        4   => 1.1,
        0   => 0,
    );

    // ------

    $arrayy = A::create($array)->sortKeys(SORT_DESC, SORT_REGULAR);
    $result = $arrayy->getArray();

    $expected = array(
        101 => 123,
        100 => 'bcd',
        99  => 'aaa',
        4   => 1.1,
        3   => 'bcde',
        1   => 'Bcde',
        0   => 0,
    );

    //var_dump(array($expected, $result));
    self::assertTrue($expected == $result);

    // ------

    $arrayy = A::create($array)->sortKeys(SORT_ASC);
    $result = $arrayy->getArray();

    $expected = array(
        0   => 0,
        1   => 'Bcde',
        3   => 'bcde',
        4   => 1.1,
        99  => 'aaa',
        100 => 'bcd',
        101 => 123,
    );

    //var_dump(array($expected, $result));
    self::assertTrue($expected == $result);
  }

  public function testOrderByValueKeepIndex()
  {
    $array = array(
        100 => 'abc',
        99  => 'aaa',
        2   => 'bcd',
        1   => 'hcd',
        3   => 'bce',
    );

    $arrayy = A::create($array)->sortValueKeepIndex(SORT_DESC);
    $result = $arrayy->getArray();

    $expected = array(
        100 => 'abc',
        99  => 'aaa',
        2   => 'bcd',
        1   => 'hcd',
        3   => 'bce',
    );

    //var_dump(array($expected, $result));
    self::assertTrue($expected == $result);
  }

  public function testOrderByValueNewIndex()
  {
    $array = array(
        1   => 'hcd',
        3   => 'bce',
        2   => 'bcd',
        100 => 'abc',
        99  => 'aaa',
    );

    $arrayy = A::create($array)->sortValueNewIndex(SORT_ASC, SORT_REGULAR);
    $result = $arrayy->getArray();

    $expected = array(
        0 => 'aaa',
        1 => 'abc',
        2 => 'bcd',
        3 => 'bce',
        4 => 'hcd',
    );

    //var_dump(array($expected, $result));
    self::assertTrue($expected === $result);
  }

  public function testSortV2()
  {
    $array = array(
        1   => 'hcd',
        3   => 'bce',
        2   => 'bcd',
        100 => 'abc',
        99  => 'aaa',
    );

    $arrayy = A::create($array)->sort(SORT_ASC, SORT_REGULAR, false);
    $result = $arrayy->getArray();

    $expected = array(
        0 => 'aaa',
        1 => 'abc',
        2 => 'bcd',
        3 => 'bce',
        4 => 'hcd',
    );

    //var_dump(array($expected, $result));
    self::assertTrue($expected === $result);
  }

  /**
   * @return array
   */
  public function stringWithSeparatorProvider()
  {
    return array(
        array(
            's,t,r,i,n,g',
            ',',
        ),
        array(
            'He|ll|o',
            '|',
        ),
        array(
            'Wo;rld',
            ';',
        ),
    );
  }


  /**
   * @dataProvider stringWithSeparatorProvider
   *
   * @param string $string
   * @param string $separator
   */
  public function testCreateFromString($string, $separator)
  {
    $array = explode($separator, $string);
    $arrayy = new A($array);

    $resultArrayy = A::createFromString($string, $separator);

    self::assertImmutable($arrayy, $resultArrayy, $array, $array);
  }

  /**
   * @param A     $arrayzy
   * @param A     $resultArrayzy
   * @param array $array
   * @param array $resultArray
   */
  protected function assertImmutable(A $arrayzy, A $resultArrayzy, array $array, array $resultArray)
  {
    self::assertNotSame($arrayzy, $resultArrayzy);
    self::assertSame($array, $arrayzy->toArray());
    self::assertSame($resultArray, $resultArrayzy->toArray());
  }

  public function testCreateWithRange()
  {
    $arrayy1 = A::createWithRange(2, 7);
    $array1 = range(2, 7);
    $arrayy2 = A::createWithRange('d', 'h');
    $array2 = range('d', 'h');
    $arrayy3 = A::createWithRange(22, 11, 2);
    $array3 = range(22, 11, 2);
    $arrayy4 = A::createWithRange('y', 'k', 2);
    $array4 = range('y', 'k', 2);

    self::assertSame($array1, $arrayy1->toArray());
    self::assertSame($array2, $arrayy2->toArray());
    self::assertSame($array3, $arrayy3->toArray());
    self::assertSame($array4, $arrayy4->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testStaticCreate(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = A::create($array);

    self::assertImmutable($arrayy, $resultArrayy, $array, $array);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testStaticCreateFromJson(array $array)
  {
    $json = json_encode($array);

    $arrayy = A::create($array);
    $resultArrayy = A::createFromJson($json);

    self::assertImmutable($arrayy, $resultArrayy, $array, $array);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testStaticCreateFromObject(array $array)
  {
    $arrayy = A::create($array);
    $resultArrayy = A::createFromObject($arrayy);

    self::assertImmutable($arrayy, $resultArrayy, $array, $array);
  }

  /**
   * @dataProvider stringWithSeparatorProvider
   *
   * @param string $string
   * @param string $separator
   */
  public function testStaticCreateFromString($string, $separator)
  {
    $array = explode($separator, $string);

    $arrayy = A::create($array);
    $resultArrayy = A::createFromString($string, $separator);

    self::assertImmutable($arrayy, $resultArrayy, $array, $array);
  }

  public function testAdd()
  {
    $array = array(1, 2);
    $arrayy = new A($array);
    $resultArrayy = $arrayy->add(3);
    $array[] = 3;

    self::assertMutable($arrayy, $resultArrayy, $array);
  }

  // The public method list order by ASC

  /**
   * @param A     $arrayzy
   * @param A     $resultArrayzy
   * @param array $resultArray
   */
  protected function assertMutable(A $arrayzy, A $resultArrayzy, array $resultArray)
  {
    self::assertSame($arrayzy, $resultArrayzy);
    self::assertSame($resultArray, $arrayzy->toArray());
    self::assertSame($resultArray, $resultArrayzy->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testChunk(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->chunk(2);
    $resultArray = array_chunk($array, 2);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testClear(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->clear();

    self::assertMutable($arrayy, $resultArrayy, array());
  }

  public function testCombineTo()
  {
    $firstArray = array(
        1 => 'one',
        2 => 'two',
        3 => 'three',
    );
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($firstArray);
    $resultArrayy = $arrayy->replaceAllKeys($secondArray)->getArray();
    $resultArray = array_combine($secondArray, $firstArray);

    self::assertEquals($resultArray, $resultArrayy);
  }

  public function testCombineWith()
  {
    $firstArray = array(
        1 => 'one',
        2 => 'two',
        3 => 'three',
    );
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($firstArray);
    $resultArrayy = $arrayy->replaceAllValues($secondArray);
    $resultArray = array_combine($firstArray, $secondArray);

    self::assertImmutable($arrayy, $resultArrayy, $firstArray, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testCustomSort(array $array)
  {
    $callable = function ($a, $b) {
      if ($a == $b) {
        return 0;
      }

      return ($a < $b) ? -1 : 1;
    };

    $arrayy = new A($array);
    $resultArrayy = $arrayy->customSortValues($callable);
    $resultArray = $array;
    usort($resultArray, $callable);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testCustomSortKeys(array $array)
  {
    $callable = function ($a, $b) {
      if ($a == $b) {
        return 0;
      }

      return ($a > $b) ? -1 : 1;
    };

    $arrayy = new A($array);
    $resultArrayy = $arrayy->customSortKeys($callable);
    $resultArray = $array;
    uksort($resultArray, $callable);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testDiffWith(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->diff($secondArray);
    $resultArray = array_diff($array, $secondArray);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testMap(array $array)
  {
    $callable = function ($value) {
      return str_repeat($value, 2);
    };
    $arrayy = new A($array);
    $resultArrayy = $arrayy->map($callable);
    $resultArray = array_map($callable, $array);
    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testMergePrependNewIndexV2(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergePrependNewIndex($secondArray);
    $resultArray = array_merge($secondArray, $array);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testMergeToRecursively(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergePrependNewIndex($secondArray, true);
    $resultArray = array_merge_recursive($secondArray, $array);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testMergeWith(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergeAppendNewIndex($secondArray);
    $resultArray = array_merge($array, $secondArray);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testMergeWithRecursively(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergeAppendNewIndex($secondArray, true);
    $resultArray = array_merge_recursive($array, $secondArray);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testOffsetNullSet(array $array)
  {
    $offset = null;
    $value = 'new';

    $arrayy = new A($array);
    $arrayy->offsetSet($offset, $value);
    if (isset($offset)) {
      $array[$offset] = $value;
    } else {
      $array[] = $value;
    }

    self::assertSame($array, $arrayy->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testOffsetSet(array $array)
  {
    $offset = 1;
    $value = 'new';

    $arrayy = new A($array);
    $arrayy->offsetSet($offset, $value);
    if (isset($offset)) {
      $array[$offset] = $value;
    } else {
      $array[] = $value;
    }

    self::assertSame($array, $arrayy->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testOffsetUnset(array $array)
  {
    $arrayy = new A($array);
    $offset = 1;

    $arrayy->offsetUnset($offset);
    unset($array[$offset]);

    self::assertSame($array, $arrayy->toArray());
    self::assertFalse(isset($array[$offset]));
    self::assertFalse($arrayy->offsetExists($offset));
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testPad(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->pad(10, 5);
    $resultArray = array_pad($array, 10, 5);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testPop(array $array)
  {
    $arrayy = new A($array);
    $poppedValue = $arrayy->pop();
    $resultArray = $array;
    $poppedArrayValue = array_pop($resultArray);

    self::assertSame($poppedArrayValue, $poppedValue);
    self::assertSame($resultArray, $arrayy->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testPush(array $array)
  {
    $newElement1 = 5;
    $newElement2 = 10;

    $arrayy = new A($array);
    $resultArrayy = $arrayy->push($newElement1, $newElement2);
    $resultArray = $array;
    array_push($resultArray, $newElement1, $newElement2);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testReindex(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->reindex()->getArray();
    $resultArray = array_values($array);

    self::assertEquals(array(), array_diff($resultArrayy, $resultArray));
  }

  public function testReindexSimple()
  {
    $testArray = array(2 => 1, 3 => 2);
    $arrayy = new A($testArray);
    $arrayy->reindex();

    $result = array(0 => 1, 1 => 2);

    self::assertEquals($result, $arrayy->getArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testReplaceIn(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergePrependKeepIndex($secondArray)->getArray();
    $resultArray = array_replace($secondArray, $array);

    self::assertEquals(array(), array_diff($resultArrayy, $resultArray));
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testReplaceInRecursively(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergePrependKeepIndex($secondArray, true)->getArray();
    $resultArray = array_replace_recursive($secondArray, $array);

    self::assertEquals(array(), array_diff($resultArrayy, $resultArray));
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testReplaceWith(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergeAppendKeepIndex($secondArray)->getArray();
    $resultArray = array_replace($array, $secondArray);

    self::assertEquals(array(), array_diff($resultArrayy, $resultArray));
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testReplaceWithRecursively(array $array)
  {
    $secondArray = array(
        'one' => 1,
        1     => 'one',
        2     => 2,
    );

    $arrayy = new A($array);
    $resultArrayy = $arrayy->mergeAppendKeepIndex($secondArray, true)->getArray();
    $resultArray = array_replace_recursive($array, $secondArray);

    self::assertEquals(array(), array_diff($resultArrayy, $resultArray));
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testShift(array $array)
  {
    $arrayy = new A($array);
    $shiftedValue = $arrayy->shift();
    $resultArray = $array;
    $shiftedArrayValue = array_shift($resultArray);

    self::assertSame($shiftedArrayValue, $shiftedValue);
    self::assertSame($resultArray, $arrayy->toArray());
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSlice(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->slice(1, 1);
    $resultArray = array_slice($array, 1, 1);

    self::assertImmutable($arrayy, $resultArrayy, $array, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortAscWithoutPreserveKeys(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sort(SORT_ASC, SORT_REGULAR, false);
    $resultArray = $array;
    sort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortAscWithPreserveKeys(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sort(SORT_ASC, SORT_REGULAR, true);
    $resultArray = $array;
    asort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortDescWithoutPreserveKeys(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sort(SORT_DESC, SORT_REGULAR, false);
    $resultArray = $array;
    rsort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortDescWithPreserveKeys(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sort(SORT_DESC, SORT_REGULAR, true);
    $resultArray = $array;
    arsort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortKeysAsc(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sortKeys(SORT_ASC, SORT_REGULAR);
    $resultArray = $array;
    ksort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testSortKeysDesc(array $array)
  {
    $arrayy = new A($array);
    $resultArrayy = $arrayy->sortKeys(SORT_DESC, SORT_REGULAR);
    $resultArray = $array;
    krsort($resultArray, SORT_REGULAR);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testUnshift(array $array)
  {
    $newElement1 = 5;
    $newElement2 = 10;

    $arrayy = new A($array);
    $resultArrayy = $arrayy->unshift($newElement1, $newElement2);
    $resultArray = $array;
    array_unshift($resultArray, $newElement1, $newElement2);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testWalk(array $array)
  {
    $callable = function (&$value, $key) {
      $value = $key;
    };

    $arrayy = new A($array);
    $resultArrayy = $arrayy->walk($callable);
    $resultArray = $array;
    array_walk($resultArray, $callable);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }

  /**
   * @dataProvider simpleArrayProvider
   *
   * @param array $array
   */
  public function testWalkRecursively(array $array)
  {
    $callable = function (&$value, $key) {
      $value = $key;
    };

    $arrayy = new A($array);
    $resultArrayy = $arrayy->walk($callable, true);
    $resultArray = $array;
    array_walk_recursive($resultArray, $callable);

    self::assertMutable($arrayy, $resultArrayy, $resultArray);
  }
}
