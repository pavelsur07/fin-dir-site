# Upgrade

Изменить version+digest, прочитать vendor migration/security notes, пересобрать
optimized image, прогнать Identity CI/настоящий consumer и восстановление backup.
До production проверить staging на отдельной ephemeral БД/credentials/keys.
На общем хосте не запускать постоянный второй IAM без оценки памяти.

Rollback matrix: config-only без schema change — предыдущий verified release,
проверить diff и manifest. Vendor schema upgrade — старый image на новой БД
не запускать. Нужен restore старой БД на отдельный volume, старые credentials,
тот же issuer и проверенный cutover. First install без прежней IAM БД — остановка
только новых IAM containers; volumes сохранить для диагностики. Website release
не переключается ни в одном варианте Identity rollback.
