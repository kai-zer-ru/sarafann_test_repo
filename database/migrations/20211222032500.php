<?php
declare(strict_types=1);

use App\Models\ApiAccessTokenType;
use App\Models\ApiMethod;
use App\Models\ApiMethodGroup;
use App\Models\ApiVersion;
use App\SaraFann;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration;

final class V20211222032500 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        /** @var Table $table */
        $table = $this->table('users');
        $table->addColumn('email', 'string', ['limit' => 255]);
        $table->addColumn('first_name', 'string', ['limit' => 255]);
        $table->addColumn('last_name', 'string', ['limit' => 255]);
        $table->addColumn('phone', 'string', ['limit' => 255]);
        $table->addColumn('code', 'integer');
        $table->addColumn('status', 'integer', ['limit' => 255]);
        $table->addColumn('description', 'text');
        $table->addTimestamps();
        $table->save();

        /** @var Table $table2 */
        $table2 = $this->table('api_access_token_types');
        $table2->addColumn('type', 'string', ['limit' => 255]);
        $table2->addColumn('name', 'string', ['limit' => 255]);
        $table2->addColumn('description', 'text');
        $table2->addTimestamps();
        $table2->save();

        /** @var Table $table3 */
        $table3 = $this->table('api_error_codes');
        $table3->addColumn('code', 'integer');
        $table3->addColumn('title', 'string', ['limit' => 255]);
        $table3->addColumn('description', 'text');
        $table3->addTimestamps();
        $table3->save();

        /** @var Table $table4 */
        $table4 = $this->table('api_methods');
        $table4->addColumn('group', 'string', ['limit' => 255]);
        $table4->addColumn('name', 'string', ['limit' => 255]);
        $table4->addColumn('description', 'text');
        $table4->addColumn('response_description', 'text');
        $table4->addColumn('function_name', 'string', ['limit' => 255]);
        $table4->addColumn('status', 'integer');
        $table4->addColumn('first_version', 'integer');
        $table4->addColumn('last_version', 'integer');
        $table4->addColumn('is_hidden', 'integer');
        $table4->addColumn('is_deprecated', 'integer');
        $table4->addTimestamps();
        $table4->save();

        /** @var Table $table5 */
        $table5 = $this->table('api_method_groups');
        $table5->addColumn('name', 'string', ['limit' => 255]);
        $table5->addColumn('description', 'text');
        $table5->addTimestamps();
        $table5->save();

        /** @var Table $table6 */
        $table6 = $this->table('api_versions');
        $table6->addColumn('version_id', 'float', ['limit' => 255]);
        $table6->addColumn('description', 'text');
        $table6->addTimestamps();
        $table6->save();



        // создание методов апи
        new SaraFann();
        // только при создании новой версии
        ApiVersion::create([
            'version_id' => 1.0,
            'description' => 'First version',
        ]);
        // создание группы если нет ещё
        ApiMethodGroup::create([
            'name' => 'users',
            'description' => 'Work with users',
        ]);
        // только при использовании существующей версии
        $version = ApiVersion::orderByDesc('id')
            ->first();



        $method = ApiMethod::create([
            'group' => 'users',
            'name' => 'get',
            'description' => 'Полцчение данных о пользователе',
            'response_description' => '',
            'function_name' => 'workGetUserData',
            'first_version' => $version->id,
            'last_version' => $version->id,
            'status' => ApiMethod::STATUS_ENABLED,
        ]);
        // метод доступен для авторизованного юзера
        $method->accessTokens()->attach(ApiAccessTokenType::whereType(ApiAccessTokenType::TYPE_USER)->first());
        // метод доступен для НЕ авторизованного юзера
        $method->accessTokens()->attach(ApiAccessTokenType::whereType(ApiAccessTokenType::TYPE_APPLICATION)->first());



        $method = ApiMethod::create([
            'group' => 'users',
            'name' => 'me',
            'description' => 'Полцчение данных о текущем пользователе',
            'response_description' => '',
            'function_name' => 'workGetMyData',
            'first_version' => $version->id,
            'last_version' => $version->id,
            'status' => ApiMethod::STATUS_ENABLED,
        ]);
        // метод доступен для авторизованного юзера
        $method->accessTokens()->attach(ApiAccessTokenType::whereType(ApiAccessTokenType::TYPE_USER)->first());



        // После создания метода нужно создать json файлы в директории public/json/schema/methods
        // Создать директорию с названием group (если её ещё нет) и положить туда 2 файла, get.request.json
        // и get.response.json (get - это имя метода (name)).
        // В данном случае файлы для метода workGetUserData уже лежат в нужном месте
        // Задача:
            // - добавить файлы для workGetMyData
            // - добавить функционал авторизации через одноразовый пароль. Входные данные - email или телефон.
                // У юзера есть поле code - с одноразовым паролем. Нужно добавить ему время жизни, проверки, создание
                // токена если код верный. Будет 2 этапа. Первый - генерация кода. Второй - ввод кода пользователем и
                // получение токена
    }
}
