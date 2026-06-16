<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\StaffPosition;

class DemoWorkersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $departments = $this->seedDepartments();
            $positions = $this->seedStaffPositions($departments);

            $this->seedEmployees($positions);
        });
    }

    /**
     * @return array<string, Department>
     */
    private function seedDepartments(): array
    {
        $tree = [
            'Администрация' => ['Приемная', 'Юридический отдел', 'Кадровая служба'],
            'Технический отдел' => ['Авторский надзор', 'Проектный контроль', 'Сметный отдел'],
            'Финансовый отдел' => ['Бухгалтерия', 'Казначейство', 'Планово-экономический отдел'],
            'Коммерческий отдел' => ['Продажи', 'Маркетинг', 'Клиентский сервис'],
            'Производственный отдел' => ['Участок работ', 'Снабжение', 'Склад'],
        ];

        $departments = [];
        $sortOrder = 10;

        foreach ($tree as $rootName => $childNames) {
            $rootDepartment = Department::query()->updateOrCreate(
                ['name' => $rootName],
                [
                    'parent_id' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ],
            );

            $departments[$rootName] = $rootDepartment;
            $childSortOrder = 10;

            foreach ($childNames as $childName) {
                $departments[$childName] = Department::query()->updateOrCreate(
                    ['name' => $childName],
                    [
                        'parent_id' => $rootDepartment->id,
                        'sort_order' => $childSortOrder,
                        'is_active' => true,
                    ],
                );

                $childSortOrder += 10;
            }

            $sortOrder += 10;
        }

        return $departments;
    }

    /**
     * @param  array<string, Department>  $departments
     * @return array<int, StaffPosition>
     */
    private function seedStaffPositions(array $departments): array
    {
        $positionRows = [
            ['Администрация', 'Генеральный директор'],
            ['Администрация', 'Операционный директор'],
            ['Приемная', 'Офис-менеджер'],
            ['Юридический отдел', 'Юрист'],
            ['Кадровая служба', 'HR-менеджер'],
            ['Технический отдел', 'Главный инженер'],
            ['Авторский надзор', 'Специалист авторского надзора'],
            ['Проектный контроль', 'Руководитель проекта'],
            ['Сметный отдел', 'Инженер-сметчик'],
            ['Финансовый отдел', 'Финансовый директор'],
            ['Бухгалтерия', 'Главный бухгалтер'],
            ['Казначейство', 'Казначей'],
            ['Планово-экономический отдел', 'Экономист'],
            ['Коммерческий отдел', 'Коммерческий директор'],
            ['Продажи', 'Менеджер по продажам'],
            ['Маркетинг', 'Маркетолог'],
            ['Клиентский сервис', 'Специалист поддержки'],
            ['Производственный отдел', 'Начальник производства'],
            ['Снабжение', 'Менеджер по снабжению'],
            ['Склад', 'Кладовщик'],
        ];

        $positions = [];

        foreach ($positionRows as $index => [$departmentName, $positionName]) {
            $positions[] = StaffPosition::query()->updateOrCreate(
                [
                    'department_id' => $departments[$departmentName]->id,
                    'name' => $positionName,
                ],
                [
                    'planned_count' => 3 + ($index % 5),
                    'sort_order' => ($index + 1) * 10,
                    'is_active' => true,
                ],
            );
        }

        return $positions;
    }

    /**
     * @param  array<int, StaffPosition>  $positions
     */
    private function seedEmployees(array $positions): void
    {
        $names = [
            'Аманжолов Ербол', 'Балгын Айдана', 'Ермекова Дана', 'Тасымов Руслан', 'Серикбаев Марат',
            'Нурпеисова Алия', 'Касымов Тимур', 'Абдрахманова Жанна', 'Ибраев Данияр', 'Сулейменова Мадина',
            'Омаров Асхат', 'Ахметова Гульнара', 'Калиев Бекзат', 'Рахимова Сауле', 'Жумабаев Арман',
            'Байжанова Назерке', 'Муратов Нурлан', 'Есимова Карина', 'Тулегенов Азамат', 'Кенжебаева Асем',
            'Садыков Роман', 'Мухамедьярова Лаура', 'Абылкасымов Самат', 'Жаксылыкова Индира', 'Токтаров Алибек',
            'Мельникова Ольга', 'Петров Андрей', 'Сидорова Виктория', 'Ким Александр', 'Цой Марина',
            'Ли Виктор', 'Григорьева Анна', 'Васильев Павел', 'Николаева Елена', 'Федоров Илья',
            'Смирнова Дарья', 'Кузнецов Максим', 'Попова Наталья', 'Волков Артем', 'Соколова Ирина',
            'Морозов Денис', 'Новикова Камила', 'Орлов Кирилл', 'Егорова Валерия', 'Зайцев Михаил',
            'Павлова Ксения', 'Белов Константин', 'Комарова Светлана', 'Гусев Алексей', 'Титова Яна',
        ];

        foreach ($names as $index => $name) {
            $position = $positions[$index % count($positions)];
            $email = 'demo.employee.'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'@sentinel.local';

            /** @var Employee $employee */
            $employee = Employee::withTrashed()->updateOrCreate(
                ['email' => $email],
                [
                    'user_id' => null,
                    'full_name' => $name,
                    'phone' => '+7 777 '.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                    'position' => $position->name,
                    'staff_position_id' => $position->id,
                    'status' => EmployeeStatus::Active,
                ],
            );

            if ($employee->trashed()) {
                $employee->restore();
            }

            $employee->departments()->syncWithoutDetaching([$position->department_id]);
        }
    }
}
