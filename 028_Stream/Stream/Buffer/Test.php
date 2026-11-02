<?php

declare(strict_types=1);

//use PHPUnit\Framework\TestCase;

class Stream_Buffer_Test //extends TestCase
{
  private IStream_Buffer $buffer;

//protected function setUp(): void { $this->buffer = new StreamBuffer(); }

  public function test_has_returns_false_on_empty(): void
  {
    $this->assertFalse($this->buffer->has());
  }

  public function test_has_returns_true_when_data_present(): void
  {
    $this->buffer->add('hello');
    $this->assertTrue($this->buffer->has());
  }

  public function test_restSize_returns_correct_size(): void
  {
    $this->assertEquals(0, $this->buffer->restSize());

    $this->buffer->add('abc');
    $this->assertEquals(3, $this->buffer->restSize());
  }

  public function test_add_appends_data_to_end(): void
  {
    $this->buffer->add('foo');
    $this->buffer->add('bar');

    // читаем всё, чтобы убедиться, что порядок правильный
    $this->assertEquals('foobar', $this->buffer->readAll());
  }

  public function test_get_peeks_without_consuming(): void
  {
    $this->buffer->add('hello world');

    $peeked = $this->buffer->peek(5);
    $this->assertEquals('hello', $peeked);

    // данные не должны исчезнуть
    $this->assertEquals(11, $this->buffer->restSize());
    $this->assertEquals('hello world', $this->buffer->readAll());
  }

  public function test_skip_advances_position(): void
  {
    $this->buffer->add('abcdef');

    $this->buffer->skip(2);
    $this->assertEquals(4, $this->buffer->restSize());
    $this->assertEquals('cdef', $this->buffer->readAll());
  }

  public function test_read_consumes_specified_bytes(): void
  {
    $this->buffer->add('abcdef');

    $read = $this->buffer->read(3);
    $this->assertEquals('abc', $read);
    $this->assertEquals(3, $this->buffer->restSize());
    $this->assertEquals('def', $this->buffer->readAll());
  }

  public function test_read_when_not_enough_data_returns_available(): void
  {
    $this->buffer->add('ab');

    $read = $this->buffer->read(5); // просим больше, чем есть
    $this->assertEquals('ab', $read);
    $this->assertEquals(0, $this->buffer->restSize());
  }

  public function test_readAll_returns_all_remaining_and_empties_buffer(): void
  {
    $this->buffer->add('test data');

    $all = $this->buffer->readAll();
    $this->assertEquals('test data', $all);
    $this->assertEquals(0, $this->buffer->restSize());
    $this->assertFalse($this->buffer->has());
  }

  public function test_unread_adds_data_to_front(): void
  {
    $this->buffer->add('world');
    $this->buffer->unread('hello ');

    $this->assertEquals(11, $this->buffer->restSize());
    $this->assertEquals('hello world', $this->buffer->readAll());
  }

  public function test_unread_after_partial_read(): void
  {
    $this->buffer->add('xyz');
    $this->buffer->read(1); // прочитали 'x', осталось 'yz'

    $this->buffer->unread('abc'); // добавляем в начало

    // теперь должно быть: abc + yz
    $this->assertEquals('abcyz', $this->buffer->readAll());
  }

  public function test_chained_operations(): void
  {
    $this->buffer->add('1234567890');

    $this->buffer->skip(2);         // пропускаем '12'
    $back=false? 'AB':'12';
    $this->buffer->unread($back);       // добавляем 'AB' в начало
    $chunk = $this->buffer->peek(3); // peek 'AB3'
    $consumed = $this->buffer->read(4); // читаем 'AB34'

    $this->assertEquals($back.'3', $chunk);
    $this->assertEquals($back.'34', $consumed);
    $this->assertEquals('567890', $this->buffer->readAll());
  }
  
  function start()
  {
    $buffers=[
      Stream_Buffer_String ::class,
      Stream_Buffer_Offset ::class, //Для Stream_Buffer_Offset: проверь, что смещение корректно работает при множественных skip/read, особенно если буфер хранит данные как срез/view, а не копирует их.
    //Stream_Buffer_List   ::class, //Для Stream_Buffer_List / Stream_Buffer_Doubly: можно добавить тесты на производительность (если важно) или на корректность работы при очень большом числе мелких чанков (например, 10 000 вызовов add по 1 байту, затем readAll).
    //Stream_Buffer_Doubly ::class,
    //Stream_Buffer_Cyclic ::class, //Для Stream_Buffer_Cyclic: тесты на <переполнение> и повторное использование места, когда данные пишутся поверх старых. Проверь, что read возвращает именно те данные, которые должны быть по логике циклического буфера, а не <всё подряд>.
    ];
    foreach ($buffers as $bufferClass)
    {
      foreach ($this->getAll() as $test)
      {
        $this->buffer = new $bufferClass();
        $test();
      }
    }
  }
  
  function getAll()
  {
    return [
      $this->test_has_returns_false_on_empty                       (...),
      $this->test_has_returns_true_when_data_present               (...),
      $this->test_restSize_returns_correct_size                    (...),
      $this->test_add_appends_data_to_end                          (...),
      $this->test_get_peeks_without_consuming                      (...),
      $this->test_skip_advances_position                           (...),
      $this->test_read_consumes_specified_bytes                    (...),
      $this->test_read_when_not_enough_data_returns_available      (...),
      $this->test_readAll_returns_all_remaining_and_empties_buffer (...),
      $this->test_unread_adds_data_to_front                        (...),
      $this->test_unread_after_partial_read                        (...),
      $this->test_chained_operations                               (...),
    ];
  }
  
  function assertFalse ($false ) { $this->assertEquals(false ,$false ); }
  function assertTrue  ($true  ) { $this->assertEquals(true  ,$true  ); }
  function assertEquals($desired ,$actual)
  {
    if ($actual !== $desired)
    {
      Logger::error('Failed:')(['desired'=>$desired])(['actual'=>$actual]);
      throw LogicException();
    }
  //else
  //  Logger::log($k, ' Ok');
  }
}