This project has been developed over a couple sessions as a way to evaluate PHP in 2016.


## Status

The logic is complete but it does not quite run in Apache (time constraints).

The test case runs most of the logic.


* index.php is the web entry point
* test.php is the test entry point
* sql.php is the User model
* core.php is the main REST handler

Hitting the `/test.php` endpoint from Docker will run test cases there.

### TODO 

* [ ] URL mapping
* [ ] Test cases for HTTP errors
* [ ] Test cases for REST mapping

## Usage

It's based on [this](https://hub.docker.com/_/php/) Docker image so check that out for additional details.

* `$ docker build -t rest-demo .`
* `$ docker run -d --name rest-demo -p 3000:80 rest-demo`
