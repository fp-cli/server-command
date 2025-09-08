@require-php-5.4
Feature: Serve FinPress locally

  Scenario: Vanilla install
    Given a FP install
    And I launch in the background `fp server --host=localhost --port=8181`
    And I run `fp option set blogdescription 'Just another FinPress site'`

    When I run `curl -sS localhost:8181`
    Then STDOUT should contain:
      """
      Just another FinPress site
      """

    When I run `curl -sS localhost:8181/license.txt > /tmp/license.txt`
    And I run `cmp /tmp/license.txt license.txt`
    Then STDOUT should be empty
