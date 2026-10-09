# Release notes

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
