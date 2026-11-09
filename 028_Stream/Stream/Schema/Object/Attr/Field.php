<?
#[Attribute(Attribute::TARGET_PROPERTY)]
class Stream_Schema_Object_Attr_Field
{
  public function __construct(
    public  mixed  $type, // ћожет быть строкой ('u8', 'i32l'), интом (fixed) или именем класса вложенной структуры
    public ?string $prefixed = null // ≈сли строка или байты предвар€ютс€ префиксом длины (например, 'u16b')
  ) {}
}
