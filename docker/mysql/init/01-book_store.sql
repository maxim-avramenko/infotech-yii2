CREATE DATABASE IF NOT EXISTS `book_store`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `book_store_test`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `book_store`.* TO 'infotech'@'%';
GRANT ALL PRIVILEGES ON `book_store_test`.* TO 'infotech'@'%';
FLUSH PRIVILEGES;
