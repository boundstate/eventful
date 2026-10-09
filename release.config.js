// Craft shows the changelog in the Plugin Store and the Updates utility, and
// styles GitHub alerts there. Updates that contain an alert are expanded.
// https://craftcms.com/docs/5.x/extend/changelogs-and-updates.html

// Craft requires version headings in the `## X.Y.Z - YYYY-MM-DD` format.
const headerPartial = '## {{version}} - {{date}}';

// A copy of the `conventional-changelog-conventionalcommits@9` template, with
// breaking changes rendered as an alert instead of a `### ⚠ BREAKING CHANGES`
// heading. The preset titles every note "BREAKING CHANGES", so there's only
// ever one note group. Preset v10 replaces Handlebars with render functions, but needs
// `conventional-changelog-writer@9`, which `@semantic-release/release-notes-generator`
// only supports from v15 (in beta). A single breaking change isn't put in a list.
const note = '{{#if commit.scope}}**{{commit.scope}}:** {{/if}}{{text}}';
const mainTemplate = `{{> header}}
{{#if noteGroups}}
{{#each noteGroups}}

> [!IMPORTANT]
{{#if notes.[1]}}
{{#each notes}}
> * ${note}
{{/each}}
{{else}}
{{#each notes}}
> ${note}
{{/each}}
{{/if}}
{{/each}}
{{/if}}
{{#each commitGroups}}

{{#if title}}
### {{title}}

{{/if}}
{{#each commits}}
{{> commit root=@root}}
{{/each}}
{{/each}}
{{> footer}}
`;

export default {
  branches: ['main'],
  plugins: [
    [
      '@semantic-release/commit-analyzer',
      {
        preset: 'conventionalcommits',
      },
    ],
    [
      '@semantic-release/release-notes-generator',
      {
        preset: 'conventionalcommits',
        writerOpts: { headerPartial, mainTemplate },
      },
    ],
    [
      '@semantic-release/changelog',
      {
        changelogTitle: '# Release notes',
      },
    ],
    [
      '@semantic-release/git',
      {
        assets: ['CHANGELOG.md'],
        message:
          'chore(release): ${nextRelease.version}\n\n${nextRelease.notes}',
      },
    ],
    '@semantic-release/github',
  ],
};
