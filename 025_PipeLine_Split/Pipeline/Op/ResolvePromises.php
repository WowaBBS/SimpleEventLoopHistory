<?php
/** ќператор параллельного разрешени€ промисов */
class Pipeline_Op_ResolvePromises extends Pipeline
{
  protected function onProcess(mixed $item): void
  {
    if (is_array($item))
    {
      $promises = [];
      foreach ($item as $key => $value)
        if ($value instanceof Promise)
          $promises[$key] = $value;
      
      if (!empty($promises))
      {
        $settled = Promise::allSettled($promises)->await();
        foreach ($settled as $key => $res)
          $item[$key] = $res['status'] === 'fulfilled' ? $res['value'] : $res['reason'];
      }
      
      $this->emit($item);
    }
    else
    {
      if ($item instanceof Promise)
        $item = $item->await();
      
      $this->emit($item);
    }
  }
}
