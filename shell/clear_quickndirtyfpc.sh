#!/bin/bash

redis-cli --scan --pattern *QUICKNDIRTYFPC* | xargs redis-cli del
