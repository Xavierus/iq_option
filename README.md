All commands should be executed from project root dir.

Execute "docker-compose build"

**1 Task**
Execute "docker-compose run php php /src/init_usage.php"

**2 Task**
Execute "docker-compose run php php /src/search_files.php"

**3 Task**
Execute "docker-compose exec db mysql -u root -proot demo -e 'SELECT books.title, COUNT(books_authors.author_id) as authors_count \
FROM books \
LEFT JOIN books_authors ON books_authors.book_id = books.id \
GROUP BY books_authors.book_id, books.title \
HAVING authors_count > 2
ORDER BY NULL;'"

**4 Task (linked list)**
Execute "docker-compose run php php /src/linked_list_inversion.php"