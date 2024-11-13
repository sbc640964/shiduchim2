<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3">
        <div>
            <h2 class="text-xl font-bold">
                סטטוסי הצעה
            </h2>
            <p class="text-sm text-gray-500 mt-2 max-w-md">

            </p>
        </div>
        <div class="col-span-2">
            {{ $this->proposalStatuses }}
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3">
        <div>
            <h2 class="text-xl font-bold">
                סטטוסים עבור בחור/בחורה בהצעה
            </h2>
            <p class="text-sm text-gray-500 mt-2 max-w-md">
                הגדר סטטוסים של מצב ההצעה ביחס לכל אחד מהמדוברים, למשל ההזמנה עצמה יכולה להיות בססטוס "בהרצה" וכל אחד מהמדוברים יכול להיות בססטוס שונה, הבחור "מתקדם" והבחורה"בהשהייה קצרה"
            </p>
        </div>
        <div class="col-span-2">
            {{ $this->guyGirlStatusesInfolist }}
        </div>
    </div>
</x-filament-panels::page>
