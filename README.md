# Drupal Voting

A simple voting platform built with **Drupal 10.6.12**, developed as a backend technical assessment.

The project allows administrators to create questions and answer options, while authenticated users can vote once per question through both the Drupal interface and a custom REST API.

## Technology Stack

* Drupal 10.6.12
* PHP 8.3+
* MySQL
* Lando
* Drush
* Custom Content Entities
* Custom REST API (no JSON:API)

## Features

### Administration

* Create and edit questions.
* Create and manage answer options.
* Enable or disable questions.
* Configure whether voting results are visible after voting.
* Enable or disable voting globally.
* View the number of votes received by each answer option.

### Voting

* One vote per user per question.
* Duplicate votes prevented at the database level.
* Results displayed according to each question's configuration.

### REST API

* List published questions.
* Retrieve a question by identifier.
* Register votes.
* Retrieve voting results.

## Architecture

The project uses three custom Content Entities:

* **Question**
* **QuestionOption**
* **OptionVote**

## Project Structure

```text
web/modules/custom/drupal_voting
```

contains all custom functionality.

Configuration is stored under:

```text
config/sync
```

A Postman collection is available under:

```text
docs/Drupal-Voting-API.postman_collection.json
```

## Installation

### Requirements

* Docker
* Lando

Clone the repository and start Lando:

```bash
lando start
```

Install the project dependencies:

```bash
lando composer install
```

Import the provided database:

```bash
lando db-import database/drupal_voting.sql.zip
```

Rebuild caches:

```bash
lando drush cr
```

The application is now ready.

## Credentials

Administrator

```text
You can log in as admin running ```lando drush uli```
```

Voter accounts

```text
username: eric.vvf
Password: Lu15M@r1@GAV
```

```text
Email: eric.lmhv
Password: Lu15M@r1@
```

> Replace the password above if different in the provided database.

## REST API

### List Questions

```http
GET /api/v1/questions
```

### Get Question

```http
GET /api/v1/questions/{identifier}
```

### Register Vote

```http
POST /api/v1/questions/{identifier}/vote
```

Request body

```json
{
  "email": "user@example.com",
  "option_id": 1
}
```

### Get Results

```http
GET /api/v1/questions/{identifier}/results
```

## Security Notes

* Only published questions are exposed.
* Users may vote only once per question.
* Duplicate votes are prevented using a database unique constraint.
* Business rules are centralized in service classes.
* Controllers contain only HTTP transport logic.

## Design Decisions

The API identifies voters by their email address.

This decision was made because the assessment does not define an authentication mechanism.

In a production environment, the API should be protected by a proper authentication solution such as OAuth 2.0, OpenID Connect or JWT, with the external application responsible for authenticating its users before invoking the API.

## Testing

A Postman collection is included in the repository to test every available endpoint.


## Using the Application

### Administrator Workflow

After logging in as an administrator:

1. Navigate to **Content → Questions**.
2. Click **Add Question**.
3. Fill in the question details:

   * Title
   * Unique Identifier
   * Description (optional)
   * Published status
   * Show Results setting
4. Click **Save and Configure Answer Options**.
5. Create one or more answer options for the question.
6. Each answer option may include:

   * Title
   * Description
   * Image (optional)
   * Published status
7. After saving the options, return to **Content → Questions**.
8. At any time, click **Manage Answer Options** from the Operations menu to edit the available answers or view how many votes each option has received.

### Voter Workflow

1. Log in using a user account with the **Voter** role.
2. You will be redirected automatically to the list of available questions.
3. Click **Vote** on any published question.
4. Select one answer option.
5. Submit your vote.
6. A user can vote only once per question.
7. If the question is configured to display results, the results page will be shown after voting.

### Global Voting Configuration

Voting can be enabled or disabled for the entire application.

Navigate to:

**Administration → Configuration → System → Drupal Voting**

or directly:

```text
/admin/config/system/drupal-voting
```

When voting is disabled:

* The Drupal voting interface becomes unavailable.
* The REST API refuses new voting requests.
* Existing questions remain available for administration.

### REST API

The application exposes the following endpoints:

| Method | Endpoint                                 | Description                                                        |
| ------ | ---------------------------------------- | ------------------------------------------------------------------ |
| GET    | `/api/v1/questions`                      | Returns all published questions.                                   |
| GET    | `/api/v1/questions/{identifier}`         | Returns a question and its answer options.                         |
| POST   | `/api/v1/questions/{identifier}/vote`    | Registers a vote using the user's email address.                   |
| GET    | `/api/v1/questions/{identifier}/results` | Returns the voting results when enabled for the selected question. |

### Business Rules

* Only published questions are available for voting.
* Only published answer options can receive votes.
* A user may vote only once per question.
* Duplicate votes are prevented at the database level.
* Voting can be globally enabled or disabled through the administration interface.
* Results are displayed only when the corresponding question allows it.


## Author

Eric Vinicius
