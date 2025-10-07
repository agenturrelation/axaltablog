FROM nginx:alpine

COPY nginx.conf /etc/nginx/nginx.conf
COPY mime.types/ /etc/nginx/mime.types
COPY sites/ /etc/nginx/sites-available
COPY conf.d/ /etc/nginx/conf.d

WORKDIR /var/www

CMD ["nginx"]

EXPOSE 81 444
