<?php
/** Оператор параллельного разрешения промисов */
class Pipeline_Op_Concurrent extends Pipeline_Base
{
  private  int      $concurrency =  1;
  private  int      $depth       =  2;
  private  array    $active      = [];
  private ?Deferred $deferred    =null;

  public function __construct(Pipeline $parent, int $concurrency = 1, int $depth = 2)
  {
    $this->concurrency = $concurrency;
    
    parent::__construct($parent);
  }
  
  protected static function collectPromises(&$errors, &$promises, &$item, $depth)
  {
    if ($depth <= 0) return;

    // Если прилетел одиночный промис
    if ($item instanceof Promise)
    {
      if ($item->isPending())
      {
        $promises[spl_object_id($item)] = $item;
        return;
      }
      
      if ($item->isFulfilled())
      {
        $item=$item->await();
        return;
      }
      
      $errors[spl_object_id($item)] = $item;
      return;
    }
    
    // Если на вход прилетел массив, содержащий промисы (например, из map)
    if (is_array($item))
    {
      foreach ($item as &$value)
        self::collectPromises($errors, $promises, $value, $depth-1);
      return;
    }
  }
  
  protected function addActive(mixed $item, bool $resolved=false): bool
  {
    $errors   = [];
    $promises = [];
    self::collectPromises($errors, $promises, $item, $this->depth);
    if ($errors)
    {
      //TODO: abort other promices
      $this->emitError(new AggregateException('Concurrent operator catched errors', $errors));
      return true;
    }
    
    if (!$promises)
    {
      // Обычный элемент просто пробрасываем дальше
      $this->emit($item);
      return true;
    }
    
    if ($resolved)
    {
      Logger::error('We have unresolved promises:') //Logic error
        ->dump($promises);
      return false;
    }
    
    if (count($this->active) < $this->concurrency)
    {
      if (!$this->active)
        if ($this->deferred)
          Logger::error('We have already been waiting for result')->backTracke();
        else
          $this->deferred = new Deferred();
    
      $promise = Promise::all($promises);
      $id = spl_object_id($promise);
      $this->active[$id] = $item;
      
      // TODO: $promise->then(fn()=>$this->handler($promise), fn()=>$this->emitError($promise->await()))?
      $promise->addHandler($this->handler(...), $this);
      if (count($this->active) < $this->concurrency)
        return true;
    }
    
    return false;
  }

  protected function onProcessWithChild(Pipeline_Event $type, mixed $payload, Pipeline $child): void
  {
    if ($type->isData())
    {
      if (!$this->addActive($payload))
        $this->deferred->Promise()->await();
      return;
    }
    
    if ($type->isEnd())
    {
      while ($this->active)
        $this->deferred->Promise()->await();
      $this->emitEnd();
      return;
    }
    
    $child->process($type, $payload);
  }
  
  private function handler($promise)
  {
    $id = spl_object_id($promise);
    $active = $this->active[$id];
    unset($this->active[$id]);
    $deferred = $this->deferred; $this->deferred = null;
    if ($this->active)
      $this->deferred = new Deferred();
    
    if ($promise->isFulfilled())
      $this->addActive($active, true); // TODO: $this->emit($promise->await());
    else
      $this->emitError($promise->getThrowable());
    
    $deferred->resolve();
  }
  
//private function childLocked() {}
//private function childUnlocked(bool $check=true) {}
}