-- runs once, on the first start of an empty MySQL volume: database used by the test suite
CREATE DATABASE IF NOT EXISTS library_test;
GRANT ALL PRIVILEGES ON library_test.* TO 'app'@'%';
