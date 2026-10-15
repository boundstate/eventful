# Release notes

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
