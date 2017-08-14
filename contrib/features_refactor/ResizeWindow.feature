Feature: Test resizing the browser window

Scenario Outline: Test resizing the browser window to multiple resolutions
Given I resize the browser window to "<width>" x "<height>"
Then I wait until "window.outerWidth == <width>"
And I wait until "window.outerHeight == <height>"

Scenarios:
| width | height |
| 640   | 480    |
| 800   | 600    |
| 1024  | 768    |
| 1600  | 900    |
| 1920  | 1080   |
| 4096  | 2304   |

Scenario: Test maximizing the browser window
Given I maximize the browser window
Then I wait until "screen.availWidth == window.outerWidth"


