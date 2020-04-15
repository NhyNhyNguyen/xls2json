#use base image
FROM php

#copy logic local to host
COPY "./" "/app"

WORKDIR /app