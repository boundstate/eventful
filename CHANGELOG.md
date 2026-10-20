# Release notes

## 2.2.0 - 2026-10-20
### Features

* **cp:** add a status filter to the calendar ([7a7ed4b](https://github.com/boundstate/eventful/commit/7a7ed4bec0346c483fd730b2b0197bf28d417efc))
* **cp:** default new events to user's preferred timezone ([77b191e](https://github.com/boundstate/eventful/commit/77b191e137844daaee26b4e07d1a769e1d1ee145))
* **cp:** display city for selected timezone ([1aa9335](https://github.com/boundstate/eventful/commit/1aa93355eac4b756511f848c7de33d7dfd6cbc1f))
* **cp:** improve timezone picker ux ([21d13dd](https://github.com/boundstate/eventful/commit/21d13dd4a5227320ace765a4a07257a11feefbce))
* **cp:** link the title in calendar popovers to the edit page, with the event type above it ([9b06fe8](https://github.com/boundstate/eventful/commit/9b06fe8a4fabbfe3ff60aadf2da5b6b492a35aa4))
* **cp:** match the field settings to Craft's Date field ([32d7f78](https://github.com/boundstate/eventful/commit/32d7f78cbdc904ce060ffdb7b98c9cafc56456e0))
* **cp:** open the editor slideout when double-clicking calendar events ([9be1a85](https://github.com/boundstate/eventful/commit/9be1a85e1ddf520e07835fdacad59d50b7fe91a2))
* **cp:** override Craft's timezone inputs ([ad88a5e](https://github.com/boundstate/eventful/commit/ad88a5e05d88f605f0980f5f3053ad6be08cbf0f))
* **cp:** show the calendar in the user's preferred timezone ([8c8a13f](https://github.com/boundstate/eventful/commit/8c8a13f88664b02e1a9969b613ed383d7328fc4d))
* **cp:** show the event's status in calendar popovers ([b011d46](https://github.com/boundstate/eventful/commit/b011d46e947772546a05912b99228a3bc6c90833))
* end date for all-day events ([517e7b6](https://github.com/boundstate/eventful/commit/517e7b6f9b862c2cfc79e6100ca4ddc18bac9afe))
* event to customize the organizer filter query ([9051890](https://github.com/boundstate/eventful/commit/9051890a854d82872cd7b0a62c14008e7bc65ed5))
* event to override the timezone labels ([7a2a57a](https://github.com/boundstate/eventful/commit/7a2a57acd4178bb07be433e152a70468765043ef))
* format any event date with eventDate filter and EventDateHelper::formatDate() ([c467530](https://github.com/boundstate/eventful/commit/c4675301557314f9cfa536044f6b0866e7c8b6c9))
* get a query for the events of each source with getEventQueries() ([e7b68ba](https://github.com/boundstate/eventful/commit/e7b68ba2a5a7ef674b5b5430fa71e86e1815a997))
* show fields based on whether an event repeats or is all day ([334de2c](https://github.com/boundstate/eventful/commit/334de2c04b7f5601697fc0e502544d93b7b8e08e))

### Bug Fixes

* apply the constraint in getOccurrences() to events that don't repeat ([1eee142](https://github.com/boundstate/eventful/commit/1eee14250da0829b53a473f60e2a666f1e2784cc))
* **cp:** calendar browser history ([5e99ead](https://github.com/boundstate/eventful/commit/5e99ead9a4b9479874d9dc4f50b9c11c24b951a8))
* **cp:** delete the right occurrence from the calendar for events in other timezones ([2ba7e59](https://github.com/boundstate/eventful/commit/2ba7e598494f845cb8ec2fd0ea1b56033a6732e3))
* **cp:** display time zones in calendar popup when they differ from user's ([e540206](https://github.com/boundstate/eventful/commit/e5402063cddf689447d58952f1470778f7394d2a))
* **cp:** handle timezones newer than the intl extension's data ([d082d21](https://github.com/boundstate/eventful/commit/d082d213be82094f7a5e51b8a200374106c3da33))
* **cp:** keep weekly repeat weekdays when start date doesn't change ([f802893](https://github.com/boundstate/eventful/commit/f80289319a0671f0e0092eea1a816af787b639e4))
* **cp:** read dates from mobile date inputs as local dates ([cc72502](https://github.com/boundstate/eventful/commit/cc72502847b89621b6f83d88f389f03281679bf0))
* **cp:** show all-day events on the right day in element indexes ([e5fcdc2](https://github.com/boundstate/eventful/commit/e5fcdc2bd9c8d6a85b233dfc4d201db8d32aba0b))
* **cp:** show event dates in event's timezone ([1e37114](https://github.com/boundstate/eventful/commit/1e371149c3d52be1856ac8f4184ccc1bb9f8a7f0))
* **cp:** show events ending early the next morning on their start day only ([02f9cc5](https://github.com/boundstate/eventful/commit/02f9cc5585e88c77c3f115ec24428d08bcb771f1))
* **cp:** show the selected view in the calendar's view menu ([f941fd8](https://github.com/boundstate/eventful/commit/f941fd8b402303d293514184eb9393a3fd18f66a))
* **cp:** show unpublished events (e.g. disabled or pending) as hollow instead of struck through ([28f83e4](https://github.com/boundstate/eventful/commit/28f83e477484a5a7b2d8ac55e28e836edd8ea504))
* **cp:** style calendar block events consistently with dot events ([938058e](https://github.com/boundstate/eventful/commit/938058e4c04fbbf02c0302ead9d3d1b771f1e513))
* **cp:** update timezone dropdown labels when start date changes ([e3b22ee](https://github.com/boundstate/eventful/commit/e3b22ee2ae98853fbb6525315441629e5fed540a))
* **cp:** use RRULE weekdays for monthly repeats ([645921b](https://github.com/boundstate/eventful/commit/645921bc3a64dd3e80b8245b59750ed16718d6eb))
* don't fail when saving a new event element directly ([33434da](https://github.com/boundstate/eventful/commit/33434da928aa811d89ea14e603ded69decec3ea5))
* don't fail when skipping the format of the eventDate filter ([ff495bb](https://github.com/boundstate/eventful/commit/ff495bbc0049779ed02da843c218c2f95541548c))
* format dates and times in site locale ([d75d12a](https://github.com/boundstate/eventful/commit/d75d12a2a9821e12a45a0f6dc5f629fec402151e))
* increase a new event's sequence on its first change, instead of leaving it at 0 ([819298a](https://github.com/boundstate/eventful/commit/819298a973316431f6dc304285e04cfe1113aa9d))
* increase an event's sequence when it's deleted or restored, so calendars apply the change ([5936086](https://github.com/boundstate/eventful/commit/5936086c0a9c2819c4868a35f7e9a8888b44d8fa))
* keep days of all-day events in system timezone ([b32d35a](https://github.com/boundstate/eventful/commit/b32d35a1f6cca5b82308c55f98c7f292a6ebdab3))
* keep paragraphs and line breaks in ICS descriptions ([23d4ffe](https://github.com/boundstate/eventful/commit/23d4ffee5be118f4fd37060caa0d734d62ae51a2))
* rename the after build ICS event from `beforeExport` to `afterBuildIcs` ([a7eb124](https://github.com/boundstate/eventful/commit/a7eb124644d0dc419bc1924c93ab0a5ab7ddfc61))
* store all-day events in system timezone ([67c4442](https://github.com/boundstate/eventful/commit/67c4442ca5fb3d3a567860d6c281cec3228b09e3))
* throw a clear exception for an unknown event source key ([150ba00](https://github.com/boundstate/eventful/commit/150ba0034869acb4d0a0c363ef8c0d05be8232eb))
* throw an error for an invalid event date format ([09b6d52](https://github.com/boundstate/eventful/commit/09b6d52f4b90d58d71ded9710399133997596352))
* use a hard-deleted event's latest sequence in its ICS, instead of 0 ([5bca8d7](https://github.com/boundstate/eventful/commit/5bca8d7da8f263d091ea448492ccd40bf8c44831))

## 2.1.0 - 2026-10-15
### Features

* get events & occurrences in range ([11e07d9](https://github.com/boundstate/eventful/commit/11e07d9600016827323ee74136913163d140fa8a))
* improve caching of event properties ([136a1dd](https://github.com/boundstate/eventful/commit/136a1dd0ba2ddff69d172199fbf33197de2433cf))
* option to include in-progress occurrences when getting next ([d3c7f44](https://github.com/boundstate/eventful/commit/d3c7f44c5f0e4dd2958a033d98b7828a5cbbcad4))

### Bug Fixes

* clone constraint when getting occurrences ([2e356ff](https://github.com/boundstate/eventful/commit/2e356ff12e105b812cdd6966eed1992d6d679ac5))
* don't duplicate occurrences for inclusions on a repeat date ([0023f38](https://github.com/boundstate/eventful/commit/0023f3859596ed8122cea9650ba091ccf0d771d1))
* find the next occurrence of events with more past occurrences than the virtual limit ([fd90b99](https://github.com/boundstate/eventful/commit/fd90b99360b35bace85c5face15ae4ce254ec8a8))
* optimize finding the last end of an until rule ([99fe32e](https://github.com/boundstate/eventful/commit/99fe32e16dd6489c4cfea770f16dcd216b97beac))

## 2.0.1 - 2026-10-15
### Bug Fixes

* find the last end date of events with more than 732 occurrences ([3dd4945](https://github.com/boundstate/eventful/commit/3dd49458a3eef641a3df9d16bba0c097c1f58d7d))
* **ics:** convert dates in fixed offset timezones to UTC ([a8b24a7](https://github.com/boundstate/eventful/commit/a8b24a78473da3325282ca6a783cc8c2ea32755c))
* **ics:** define timezones for inclusions before the start ([05c42df](https://github.com/boundstate/eventful/commit/05c42dfd1f42e3ab944a8544c4cc3122863fc4b1))
* **ics:** don't define a timezone for UTC ([52e8475](https://github.com/boundstate/eventful/commit/52e8475da98ccbb075c32b2afb85dd79b2addb34))
* **ics:** include seconds in timezone offsets ([9d97fdb](https://github.com/boundstate/eventful/commit/9d97fdb4f84e646b48584077e89165206833b830))
* **ics:** timezone definitions ([b6e7a77](https://github.com/boundstate/eventful/commit/b6e7a77ba7b1aac0976515d4310b8665de15cd3e))
* include field label in validation errors ([d2cf5f7](https://github.com/boundstate/eventful/commit/d2cf5f7a733e0dcc805437af0e66d493bbbd4881))

## 2.0.0 - 2026-10-09
> [!IMPORTANT]
> `EventDate::getNextOccurrence()` now only returns a future occurrence, instead of the first occurrence today.

### Features

* **field:** optional all day events ([2b9ba9d](https://github.com/boundstate/eventful/commit/2b9ba9df815d279c94f236f46693f656eef4b10e))

### Bug Fixes

* all day events in ICS exports and repeating rules ([0144229](https://github.com/boundstate/eventful/commit/01442294feac9d375283f6bc747ad1b71195c748))
* all day events in twig filters ([d37adcf](https://github.com/boundstate/eventful/commit/d37adcfc7307533e86f2eb04aaf53f60b87c5576))
* compare the calendar secret in constant time ([0d891d3](https://github.com/boundstate/eventful/commit/0d891d39c20b6acc6effeae8e7fb456d7f17a6ed))
* consistent separator for repeating event date ranges ([dccc61e](https://github.com/boundstate/eventful/commit/dccc61e062faa2311c456d004ba94d2b81c16d62))
* end an event the next day if end time is before start time ([86b99ab](https://github.com/boundstate/eventful/commit/86b99ab590d576a76a0087e98184dd9c9ef1530d))
* **field:** always fill the start & end times ([b9b6a30](https://github.com/boundstate/eventful/commit/b9b6a302ef9b65bd4fca1df07d625e8d60f140cf))
* **field:** never-ending events dropping out of the calendar ([c1f0e21](https://github.com/boundstate/eventful/commit/c1f0e21afccb643eefc76b3e4d152b3c110a82d5))
* **ics:** skip events without dates in exports ([0793205](https://github.com/boundstate/eventful/commit/0793205e7e74f9f90a05921171acfc20415aeeea))
* only return occurrences that start after now ([7bb1d0c](https://github.com/boundstate/eventful/commit/7bb1d0c92f60668a2160f6911e025d219bb78d34))
* refresh occurrences and repeat description when the rule is refreshed ([925e422](https://github.com/boundstate/eventful/commit/925e4228dec944b0fa758ac30c3b588d56de60e1))
* require post request to delete event occurrence ([da0378e](https://github.com/boundstate/eventful/commit/da0378e2fb1a3d3e56b07721d097bdcd65503e54))
* show both dates for events that end the next day ([76cc0a0](https://github.com/boundstate/eventful/commit/76cc0a06ed4646a57a29d155b5b36ffc2ec6ea61))
* throw 400 when events action is passed invalid dates ([6b29579](https://github.com/boundstate/eventful/commit/6b29579cdc6348897367d4eed8e52ec7bc8fdff3))

## 1.3.1 - 2026-10-08
### Bug Fixes

* **field:** don't overwrite criteria ([eda28a2](https://github.com/boundstate/eventful/commit/eda28a2c7f329e0f05d6225f582c06cd1b402973))
* find occurrences of long-running events within a date range ([a4acf72](https://github.com/boundstate/eventful/commit/a4acf722e9c1af0d0eba4ce8b307bf2cc361327d))

## 1.3.0 - 2026-10-08
### Features

* translations ([e5410ca](https://github.com/boundstate/eventful/commit/e5410cac88d664d031fc71d4e584931cacacef1c))

### Bug Fixes

* handle multibyte repeat descriptions ([9347cb6](https://github.com/boundstate/eventful/commit/9347cb65d93eb2eb3d98ba07837ebed1fc68eedf))
* **ics:** handle timezones with half-hour offsets ([df81c7a](https://github.com/boundstate/eventful/commit/df81c7a3cd4a2cef334711d100e42e3874b77ec5))
* **ics:** handle users without names ([47ebf4c](https://github.com/boundstate/eventful/commit/47ebf4c786182a905d2f1fd8d46471bffbe0af97))
* show until date in event timezone ([7b3e7ae](https://github.com/boundstate/eventful/commit/7b3e7aeb2ad63085babd3cccf9da61d53756d68c))

## 1.2.0 - 2026-08-18
### Features

* avoid recalculating repeat description ([3fa800e](https://github.com/boundstate/eventful/commit/3fa800e759a98bfe32904b20e6aca5fcbc0e2cdf))

### Bug Fixes

* always store first start & last end ([97bee68](https://github.com/boundstate/eventful/commit/97bee68a9fd1bea3fe91aeefcef61405ce91279e))

## 1.1.2 - 2026-08-08
### Bug Fixes

* **field:** support readonly mode ([a162a08](https://github.com/boundstate/eventful/commit/a162a08049563a526f3e80556b680bfef821b6b0))
* remove unnecessary permission check ([cfced9d](https://github.com/boundstate/eventful/commit/cfced9d36d7e83bdfbb2f7c747843c55c33cce55))

## 1.1.1 - 2026-08-08
### Bug Fixes

* **field:** validation for infinite repeat ([f9a5f1d](https://github.com/boundstate/eventful/commit/f9a5f1d7c630e03fe66ab1327a158ae4044b20db))

## 1.1.0 - 2026-08-08
### Features

* customize event sources ([e1ddcee](https://github.com/boundstate/eventful/commit/e1ddcee0fec9e0077affd1fbc3746855be10da9f))
* improve settings ([e3fc870](https://github.com/boundstate/eventful/commit/e3fc87077cf5fea1863a411d990515d4ab81cf0c))

### Bug Fixes

* input serialization & validation ([4d15834](https://github.com/boundstate/eventful/commit/4d158347a85d61273b4778f7a122700061a0eed5))

## 1.0.0 - 2026-08-06
### Features

* initial commit ([47d35a7](https://github.com/boundstate/eventful/commit/47d35a7dcf22121e140b8709dc0bc5dcc543947d))
