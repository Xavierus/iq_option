FROM centos:7.2.1511

RUN yum check-update ; echo "updated"

RUN yum update -y \
 && yum install -y nano git

# for install php7 you need to add Remi's RPM Repository https://www.server-world.info/en/note?os=CentOS_7&p=initial_conf&f=6
RUN yum -y install http://rpms.famillecollet.com/enterprise/remi-release-7.rpm

RUN sed -i -e "s/\]$/\]\npriority=10/g" /etc/yum.repos.d/remi-safe.repo
RUN yum check-update ;  echo "updated"

RUN yum install -y \
 php71-php-cli.x86_64 \
 php71-php-pecl-apcu.x86_64 \
 php71-php-intl.x86_64 \
 php71-php-pecl-zip.x86_64 \
 php71-runtime.x86_64 \
 php71-php-common.x86_64 \
 php71.x86_64 \
 php71-php-pdo.x86_64 \
 php71-php-xml.x86_64 \
 php71-php-mysqlnd.x86_64 \
 php71-php-opcache.x86_64 \
 php71-php-imap.x86_64 \
 php71-php-mbstring.x86_64 \
 php71-php-gmp.x86_64 \
 php71-php-json.x86_64 \
 php71-php-process.x86_64 \
 php71-php-bcmath.x86_64 \
 php71-php.x86_64 \
 php71-php-pecl-xdebug.x86_64

RUN unlink /etc/opt/remi/php71/php.d/15-xdebug.ini

RUN ln -sf /usr/bin/php71 /usr/bin/php \
 && yum clean all

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin \
 && mv /usr/bin/composer.phar /usr/bin/composer

RUN groupadd www-data \
 && useradd -g www-data -s /bin/bash -p xxxx -d /home/www-data -m www-data