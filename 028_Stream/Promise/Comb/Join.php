<?
/**
 * Îæèäàåò çàâåğøåíèÿ âñåõ ïğîìèñîâ (óñïåøíîãî èëè ñ îøèáêîé).
 * Âîçâğàùàåò ÈÑÕÎÄÍÛÉ ìàññèâ ïğîìèñîâ.
 */
class Promise_Comb_Join extends Promise_Comb_Base
{
  function process(): self
  {
    foreach ($this->list as $item)
    {
      if (!($item instanceof Promise)) continue;
      if ($item->isSettled()) continue;
      
      $this->add();
      
      // Íàì âàæåí ñàì ôàêò çàâåğøåíèÿ ïğîìèñà (settle),
      // ïîıòîìó â îáà êàíàëà âåøàåì îäèí è òîò æå ñ÷åò÷èê
      $item->addHandler($this->handler(...), $this);
    }

    $this->finish(); // resolve myself

    return $this;
  }
  
  protected function handler($promise)
  {
    if ($promise?->isNotSettled()) return;
    
    $this->finish();
  }
  
  // Âîçâğàùàåì òå æå ïğîìèñû!
  protected function onFinish() { $this->resolve($this->list); }
};