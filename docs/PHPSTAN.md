# PHPStan generics

PHP 8.2 has **no runtime generics**. PHPStan still type-checks them from PHPDoc: `@template`, `@param T`, `@return list<TOut>`. This project stays at **level 5** in [`phpstan.neon.dist`](../phpstan.neon.dist) (WordPress stubs, no extra PHPStan packages). Run `ddev phpstan` or `composer phpstan` (`--memory-limit=2G`, two parallel workers).

The live examples are [`hotel_booking_array_map`](../wp-content/plugins/hotel-booking-core/inc/helpers.php) and [`hotel_booking_array_find`](../wp-content/plugins/hotel-booking-core/inc/helpers.php). Inquiry workflow uses them so call sites get `list<array{name:string,label:string}>` from Symfony `Transition` objects and `object|null` from a `callable(object): bool` instead of an untyped loop. See [`inc/workflow.php`](../wp-content/plugins/hotel-booking-core/inc/workflow.php) (`hotel_booking_inquiry_enabled_transitions()`, `hotel_booking_inquiry_workflow_notes()`).

Native `array_map` / foreach do not carry that TIn → TOut through PHPStan at this level. The helpers exist for static checking; they are ordinary PHP at runtime.
