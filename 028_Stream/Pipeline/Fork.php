<?
class Pipeline_Fork extends Pipeline
{
  /** @var \WeakReference[] Храним слабые ссылки по spl_object_id на дочерние звенья */
  private array $children = [];

  /** Безопасное добавление слабой ссылки на ребенка */
  protected function registerChild(Pipeline $child): void
  {
    $this->children[spl_object_id($child)] = \WeakReference::create($child);
    $child->setParent($this);
  }

  /** Родитель сам управляет списком детей и решает, тушить ли себя */
  function unregisterChild(Pipeline $child, $reason): void
  {
    unset($this->children[spl_object_id($child)]);
    $this->childLock(false);
    
    // КРИТИЧЕСКОЕ ПРАВИЛО: Родитель отменяется только если у него
    // НЕ ОСТАЛОСЬ других активных дочерних потребителей!
    if ($this->children) return;
    $this->onNoChild($reason);
  }
  
  protected function childLock($lock)
  {
    if (!$lock)
      if (count($this->children) > 1)
        foreach ($this->children as $child)
          if ($child->get()->isLocked())
            return;
    
    parent::childLock($lock);
  }
    
  /** Раздача элемента всем детям */
  protected function onProcess(Pipeline_Event $type, mixed $payload): void
  {
    foreach ($this->children as $child)
      try
      {
        $child->get()->process($type, $payload);
      }
      catch(\Throwable $e)
      {
        $this->error('Unexpected exception')->exception($e);
      }
  }

//****************************************************************
// Debug function

  protected function buildDebugName(array &$res, $child=null)
  {
    parent::buildDebugName($res, $child);

    if(!$child) return;
    
    $children=$this->children;
    $id=spl_object_id($child);
    if(!isset($children[$id])) { $res[]='?.'; return; }
    if(count($children)===1) { $res[]='.'; return; }
    $i=0;
    foreach($children as $item_id=>$item)
      if($item_id===$id)
        break;
      else
        $i++;
    array_push($res, '[', $i, '].');
  }
//****************************************************************
}