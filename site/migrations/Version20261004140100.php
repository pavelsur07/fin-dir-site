<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DEMO — заменить реальными кейсами. Демо-данные страницы «Кейсы»: вымышленные клиенты и цифры,
 * на странице они помечены как демо. Реальные кейсы добавляются отдельно, а эта миграция откатывается.
 */
final class Version20261004140100 extends AbstractMigration
{
    private const string NBSP = "\u{00A0}";

    public function getDescription(): string
    {
        return 'DEMO: seed demo client cases';
    }

    public function up(Schema $schema): void
    {
        $nb = static fn (string $text): string => str_replace(' ', self::NBSP, $text);

        $cases = [
            [
                'slug' => 'podryadchik-uvidel-marzhu-po-kazhdomu-obektu',
                'industry' => 'stroitelstvo',
                'title' => 'Подрядчик увидел маржу по каждому объекту',
                'problem' => 'Собственник не видел реальную прибыль: деньги лежали общей кучей, а маржа по объектам считалась «на глаз».',
                'result_value' => $nb('от 6 до 24 %'),
                'result_label' => 'маржа по объектам',
                'tags' => ['ДДС', 'ОПиУ по проектам', 'ABC-анализ'],
                'featured' => true,
                'task' => 'Собственник не видел реальную прибыль: деньги лежали общей кучей, а маржа по объектам считалась «на глаз».',
                'steps' => [
                    'Загрузили ДДС и выстроили структуру учёта',
                    'Построили платёжный календарь по неделям — кассовые разрывы стали видны заранее',
                    'Посчитали ОПиУ по проектам и сделали ABC-анализ',
                ],
                'metrics' => [
                    ['value' => $nb('от 6 до 24 %'), 'label' => 'маржа по объектам'],
                    ['value' => $nb('1 850 000 ₽'), 'label' => 'кассовый разрыв, найденный в первые недели'],
                    ['value' => $nb('2 400 000 ₽'), 'label' => 'дивиденды собственнику за квартал'],
                ],
                'source' => 'Демо-данные',
            ],
            [
                'slug' => 'zakupki-na-glaz-zamorazhivali-dengi-v-ostatkah',
                'industry' => 'torgovlya',
                'title' => 'Закупки «на глаз» замораживали деньги в остатках',
                'problem' => 'Оборот есть, а свободных денег нет: закупки делались по ощущению, часть товара месяцами лежит на складе. Непонятно, какие позиции приносят прибыль, а какие замораживают деньги.',
                'result_value' => $nb('3 200 000 ₽'),
                'result_label' => 'высвобождено из остатков',
                'tags' => ['ДДС', 'ABC-анализ', 'Закупки'],
            ],
            [
                'slug' => 'oborot-rastet-a-pribyli-po-tovaram-ne-vidno',
                'industry' => 'marketplejsy',
                'title' => 'Оборот растёт, а прибыли по товарам не видно',
                'problem' => 'Выручка на площадках растёт, но на счёт приходит меньше, чем ожидали. Комиссии, логистика и возвраты съедают маржу, а по каким товарам — неизвестно.',
                'result_value' => $nb('+9 %'),
                'result_label' => 'рост маржи после пересмотра ассортимента',
                'tags' => ['ОПиУ по товарам', 'ABC-анализ'],
            ],
            [
                'slug' => 'kassovye-razryvy-sluchalis-kazhdyj-mesyac',
                'industry' => 'uslugi',
                'title' => 'Кассовые разрывы случались каждый месяц',
                'problem' => 'Деньги приходили неровно, платежи копились — о нехватке собственник узнавал в день оплаты и каждый месяц закрывал разрыв срочными решениями.',
                'result_value' => $nb('640 000 ₽'),
                'result_label' => 'кассовый разрыв найден в первые недели',
                'tags' => ['ДДС', 'Платёжный календарь'],
            ],
            [
                'slug' => 'sebestoimost-schitali-v-excel',
                'industry' => 'proizvodstvo',
                'title' => 'Себестоимость считали в Excel — и каждый раз по-разному',
                'problem' => 'Себестоимость считали в нескольких таблицах, и цифры каждый раз расходились. Прибыль по направлениям оставалась неизвестной, решения принимались на ощущениях.',
                'result_value' => $nb('21 %'),
                'result_label' => 'маржа по основному направлению',
                'tags' => ['ОПиУ по направлениям', 'SaaS вместо Excel'],
            ],
            [
                'slug' => 'kak-rasti-k-celi-po-dividendam',
                'industry' => 'it',
                'title' => 'Как расти к цели по дивидендам',
                'problem' => 'Собственник хотел выводить больше, но не понимал, за счёт чего расти к нужной цифре. Не было ни цели по дивидендам, ни связи между проектами и тем, что остаётся владельцу.',
                'result_value' => $nb('450 000 ₽'),
                'result_label' => 'дивиденды в месяц: цель и факт',
                'tags' => ['ОПиУ по проектам', 'Цель по дивидендам'],
            ],
            [
                'slug' => 'platezhnyj-kalendar-po-etapam-obekta',
                'industry' => 'stroitelstvo',
                'title' => 'Платёжный календарь по этапам объекта',
                'problem' => 'Оплаты подрядчикам и поступления от заказчика не совпадали по датам: объект то простаивал без денег, то деньги лежали без дела.',
                'result_value' => $nb('1 200 000 ₽'),
                'result_label' => 'максимальный разрыв по объекту',
                'tags' => ['ДДС', 'Платёжный календарь'],
            ],
        ];

        $base = new \DateTimeImmutable('2026-10-04 12:00:00');
        foreach ($cases as $index => $case) {
            // Порядок списка = убывание даты публикации: первый в массиве идёт первым на странице.
            $publishedAt = $base->modify(\sprintf('-%d days', $index))->format('Y-m-d H:i:s');
            $this->addSql(
                'INSERT INTO client_case (slug, industry, title, problem, result_value, result_label, tags, task, steps, metrics, source, featured, status, created_at, updated_at, published_at) '
                .'VALUES (:slug, :industry, :title, :problem, :result_value, :result_label, :tags, :task, :steps, :metrics, :source, :featured, \'published\', :at, :at, :at)',
                [
                    'slug' => $case['slug'],
                    'industry' => $case['industry'],
                    'title' => $case['title'],
                    'problem' => $case['problem'],
                    'result_value' => $case['result_value'],
                    'result_label' => $case['result_label'],
                    'tags' => json_encode($case['tags'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'task' => $case['task'] ?? null,
                    'steps' => json_encode($case['steps'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'metrics' => json_encode($case['metrics'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    'source' => $case['source'] ?? null,
                    'featured' => $case['featured'] ?? false,
                    'at' => $publishedAt,
                ],
                ['featured' => ParameterType::BOOLEAN],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM client_case WHERE slug IN ('podryadchik-uvidel-marzhu-po-kazhdomu-obektu', 'zakupki-na-glaz-zamorazhivali-dengi-v-ostatkah', 'oborot-rastet-a-pribyli-po-tovaram-ne-vidno', 'kassovye-razryvy-sluchalis-kazhdyj-mesyac', 'sebestoimost-schitali-v-excel', 'kak-rasti-k-celi-po-dividendam', 'platezhnyj-kalendar-po-etapam-obekta')");
    }
}
