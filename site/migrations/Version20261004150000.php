<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DEMO — заменить реальными кейсами. Шаги «Что сделали» для демо-кейсов из сетки, чтобы страница
 * отдельного кейса не была пустой. Главный кейс уже содержит шаги и не затрагивается.
 */
final class Version20261004150000 extends AbstractMigration
{
    private const array STEPS = [
        'zakupki-na-glaz-zamorazhivali-dengi-v-ostatkah' => [
            'Загрузили движение денег и остатки, выстроили структуру учёта',
            'Сделали ABC-анализ товарных групп: что приносит прибыль, а что лежит на складе',
            'Перестроили закупки под оборачиваемость и платёжный календарь',
        ],
        'oborot-rastet-a-pribyli-po-tovaram-ne-vidno' => [
            'Собрали отчёты площадок и выстроили учёт по каждому товару',
            'Посчитали ОПиУ по товарам с комиссиями, логистикой и возвратами',
            'Сделали ABC-анализ и пересмотрели ассортимент и закупку',
        ],
        'kassovye-razryvy-sluchalis-kazhdyj-mesyac' => [
            'Загрузили ДДС и разложили поступления и платежи по неделям',
            'Построили платёжный календарь и прогноз остатков',
            'Договорились о порядке оплат, чтобы разрывы были видны заранее',
        ],
        'sebestoimost-schitali-v-excel' => [
            'Свели данные из разных таблиц в одну систему учёта',
            'Зафиксировали правила расчёта себестоимости',
            'Посчитали ОПиУ по направлениям и показали маржу по каждому',
        ],
        'kak-rasti-k-celi-po-dividendam' => [
            'Договорились с собственником о цели по дивидендам',
            'Посчитали ОПиУ по проектам и показали вклад каждого в прибыль',
            'Построили план «цель и факт» и сверяем его на ежемесячных встречах',
        ],
        'platezhnyj-kalendar-po-etapam-obekta' => [
            'Загрузили ДДС и разбили объект на этапы',
            'Построили платёжный календарь по этапам: платежи подрядчикам и поступления от заказчика',
            'Согласовали с заказчиком график оплат, чтобы разрыв был плановым',
        ],
    ];

    public function getDescription(): string
    {
        return 'DEMO: steps for demo client cases';
    }

    public function up(Schema $schema): void
    {
        foreach (self::STEPS as $slug => $steps) {
            $this->addSql(
                'UPDATE client_case SET steps = :steps WHERE slug = :slug',
                ['steps' => json_encode($steps, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'slug' => $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::STEPS) as $slug) {
            $this->addSql("UPDATE client_case SET steps = '[]' WHERE slug = :slug", ['slug' => $slug]);
        }
    }
}
