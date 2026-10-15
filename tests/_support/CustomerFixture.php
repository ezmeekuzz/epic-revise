<?php
namespace Tests\Support;
final class CustomerFixture
{
    public static function details(): array
    {
        return ['student_id' => 'HPU12345', 'phone_number' => '3365550100', 'parent_phone_number' => '3365550101', 'parent_email_address' => 'parent@example.com', 'shipping_street' => '123 Example Street', 'shipping_city' => 'High Point', 'shipping_state' => 'NC', 'shipping_zip' => '27262'];
    }
}
