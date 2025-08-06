Я согласен.
Мы будем работать с тобой в ветке main  https://github.com/dmitriy-j/construction-rental
1. После того как мы ввели статус "В пути" у нас пропала кнопка активации аренды у арендодателя. Нам необходимо вернуть эту кнопку. Подумать как со статуса "В пути" статус заказа будет меняться на "Активен", статус позиции "В работе". Далее надо подумать как мы будем оформлять путевые листы в нашей системе, либо они будут автоматически создаваться как черновик, а мы будем заполнять их, либо создавать вручную и далее заполнять.
Прикрепляю ссылки на файлы для анализа и решения проблемы:
https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Http/Controllers/Lessor/LessorOrderController.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Http/Controllers/Lessor/DeliveryNoteController.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Http/Controllers/CheckoutController.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Http/Controllers/DeliveryController.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Http/Controllers/Lessee/OrderController.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Company.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/DeliveryNote.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Equipment.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/EquipmentAvailability.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Location.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Operator.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Order.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/OrderItem.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Platform.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/PlatformMarkup.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Models/Waybill.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Services/DeliveryNoteService.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Services/DeliveryScenarioService.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Services/EquipmentAvailabilityService.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/app/Services/PricingService.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/routes/web.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/resources/views/lessor/orders/index.blade.php

https://raw.githubusercontent.com/dmitriy-j/construction-rental/refs/heads/main/resources/views/lessor/orders/show.blade.php
