
# 🚀 Plugin Availability Condition: Enrol Dates - by LeoDG - Moodle 4.5.1+

<p align="center"><a href="https://moodle.org" target="_blank" title="Moodle Website">
  <img src="https://raw.githubusercontent.com/moodle/moodle/main/.github/moodlelogo.svg" alt="The Moodle Logo">
</a></p>

## Description new Plugin to implemment


This plugin allows teachers to restrict access to activities or sections based on user enrollment dates or course dates. Ideal for creating automated learning paths in Ivana Academy. It is the Documentaion about new Plugin Availability Condition Enrol Dates.

Folder Path this plugin: availability/condition/enrol_dates

Object: Create Availability Conditions allow teachers to restrict an activity or section so that only certain users can access it.


> Form to conditions are:

- Select DIRECTION = ['before','after']
- Select Time Value Check = ["coursetimestart","coursetimeend","enroltimestart","enroltimeend"]
- Input Time Number = Campus insert value in number to comparer
- Select Time Period = ["hours, "days", "months"]

# 📸 Demonstração

- Restriction View
![Restriction View][image1]
- Configuração do Plugin
![Configuração do Plugin][image2]
- Restriction Admin View
![Restriction Admin View][image3]

# ✨ Features
- Time Direction: Restrict before or after a specific date.
- Dynamic Triggers:
    * Course Start/End Date.
    * Student Enrollment Start/End Date.
- Flexible Periods: Configure in Hours, Days, or Months.


# 🛠 Instalação
1. Via Git (Recomendado):

> Bash
```
cd /caminho/do/seu/moodle/availability/condition
git clone https://github.com/leonardodg/availability_condition_enrol_dates.git enrol_dates
```

2. Via ZIP:
    - Baixe o código, extraia na pasta availability/condition/enrol_dates.

3. Finalização:
    - Acesse o painel de Administração do Site > Notificações para rodar o upgrade do banco de dados.

## Rules and Conditions:

- if before: allow access in context period TimeValueCheck
    Example: 10 Days before EnrolTimeEnd
        - "10" = number value insert in input
        - "Days" = Time Period selected
        - "EnrolTimeEnd" =  Time Value Check Selected
        - "Before" = Direction Selected

    Calcule: EnrolTimeEnd 10/24/2028 - 10 Days = Less because is before

- if after: allow access in context period TimeValueCheck
    Example: 1 Month after EnrolTimeStart
        - "1" = number value insert in input
        - "Month" = Time Period selected
        - "EnrolTimeStart" =  Time Value Check Selected
        - "after" = Direction Selected

    Calcule: EnrolTimeStart 10/24/2028 + 1 Month = More because is after

Database values:
  - CourseTimeStart - table mdl_courses - campus timestart
  - CourseTimeEnd - table mdl_courses - campus timeend
  - EnrolTimeStart - table mdl_enrolmment - campus timestart
  - EnrolTimeEnd - table mdl_enrolmment - campus timeend


## Prerequisites
- [Git][1]
- [Moodle][2]

### Plugin Moodle DOC
 - [Moodle Availability][3]


## DEVELOPERS

### RUN NPM DOCKER
```
apt-get update
apt-get install npm
npm install -g n
n 20.11.0
npm install -g grunt-cli
hash -r
npm install
npx grunt amd
```


### BUILD JS COMMANDS

> Build by Script:
```
    #!/bin/bash
    # Build script for availability_enrol_dates YUI plugin.
    # Run this after editing yui/src/form/js/form.js

    PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
    SRC="$PLUGIN_DIR/yui/src/form/js/form.js"
    BUILD_DIR="$PLUGIN_DIR/yui/build/moodle-availability_enrol_dates-form"
    MODULE="moodle-availability_enrol_dates-form"
    REQUIRES='"base", "node", "event", "moodle-core_availability-form"'

    # Wrap source in YUI.add(...)
    WRAPPED=$(printf "YUI.add('%s', function (Y, NAME) {\n\n%s\n\n}, '@VERSION@', {\"requires\": [%s]});\n" \
        "$MODULE" "$(cat "$SRC")" "$REQUIRES")

    echo "$WRAPPED" > "$BUILD_DIR/$MODULE-debug.js"
    echo "$WRAPPED" > "$BUILD_DIR/$MODULE.js"
    echo "$WRAPPED" > "$BUILD_DIR/$MODULE-min.js"
```

> Build by Grunt ( Recommended )
- Just Folder project:
```
npx grunt yui --path=availability/condition/enrol_dates -debug.js
php admin/cli/purge_caches.php
```

- FULL JS Moodle
```
npx grunt amd
php admin/cli/purge_caches.php
```


- BUILD JS - EXTRA:
```
npx eslint availability/condition/enrol_dates/
npx jshint --config .jshintrc availability/condition/enrol_dates/
npx grunt yui --path=availability/condition/enrol_dates 2>&1
npx grunt amd
npx grunt amd --force
```


## 🛠 Project Structure

```
 availability/condition/enrol_dates
├── classes
│   ├── condition.php
│   ├── frontend.php
├── lang
│   └── en
│       └── availability_enrol_dates.php
├── version.php
└── yui
    ├── build
    │   └── moodle-availability_enrol_dates-form
    │       ├── moodle-availability_enrol_dates-form-debug.js
    │       ├── moodle-availability_enrol_dates-form-min.js
    │       └── moodle-availability_enrol_dates-form.js
    └── src
        └── form
            ├── build.json
            ├── js
            │   └── form.js
            └── meta
                └── form.json
```

 ## 🤝 Contributing
 <img src="https://avatars.githubusercontent.com/u/1678290?s=400&u=2f875356b82f055057b6e9679c0b66001b9b29f9&v=4" title="LeoDG" >


 ## 📄 License
 This project is licensed under version 3 of the GNU General Public License *(GPL v3+)* - see the [LICENSE](LICENSE) file for details. The same licence of Moodle is provided freely as open source software.

 ## 📮 Contact
LeonardoDG - [@le0dg][4]

Github Repository Link: [github/leonardodg][5]

Website Link: [leodg.dev][6]





[1]: https://git-scm.com/book/en/v2/Getting-Started-Installing-Git
[2]: https://moodledev.io/docs/4.5
[3]: https://moodledev.io/docs/4.5/apis/plugintypes/availability
[4]: https://www.linkedin.com/in/le0dg
[5]: https://github.com/leonardodg/availability_condition_enrol_dates.git
[6]: https://leodg.dev
[image1]: https://raw.githubusercontent.com/leonardodg/availability_condition_enrol_dates/assets/images/restriction_view_studant.png

[image2]: https://raw.githubusercontent.com/leonardodg/availability_condition_enrol_dates/assets/images/restriction_setup_admin.png

[image3]: https://raw.githubusercontent.com/leonardodg/availability_condition_enrol_dates/assets/images/restriction_access_view_admin_debug.png