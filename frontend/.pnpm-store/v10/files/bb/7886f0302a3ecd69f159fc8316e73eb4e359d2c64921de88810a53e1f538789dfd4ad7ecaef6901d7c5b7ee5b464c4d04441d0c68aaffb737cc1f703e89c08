import type { AST } from "vue-eslint-parser";
declare module "estree" {
    interface NodeMap {
        VElement: AST.VElement;
        VAttribute: AST.VAttribute;
    }
}
declare const _default: {
    configs: {
        recommended: {
            parser: string;
            parserOptions: {
                ecmaVersion: 2020;
                sourceType: "module";
            };
            env: {
                browser: true;
                es6: true;
            };
            plugins: string[];
            rules: {
                "vuejs-accessibility/alt-text": "error";
                "vuejs-accessibility/anchor-has-content": "error";
                "vuejs-accessibility/aria-props": "error";
                "vuejs-accessibility/aria-role": "error";
                "vuejs-accessibility/aria-unsupported-elements": "error";
                "vuejs-accessibility/click-events-have-key-events": "error";
                "vuejs-accessibility/form-control-has-label": "error";
                "vuejs-accessibility/heading-has-content": "error";
                "vuejs-accessibility/iframe-has-title": "error";
                "vuejs-accessibility/interactive-supports-focus": "error";
                "vuejs-accessibility/label-has-for": "error";
                "vuejs-accessibility/media-has-caption": "error";
                "vuejs-accessibility/mouse-events-have-key-events": "error";
                "vuejs-accessibility/no-access-key": "error";
                "vuejs-accessibility/no-autofocus": "error";
                "vuejs-accessibility/no-distracting-elements": "error";
                "vuejs-accessibility/no-redundant-roles": "error";
                "vuejs-accessibility/no-static-element-interactions": "error";
                "vuejs-accessibility/role-has-required-aria-props": "error";
                "vuejs-accessibility/tabindex-no-positive": "error";
            };
        };
        "flat/recommended": [{
            readonly name: "vuejs-accessibility:setup:base";
            readonly plugins: {
                readonly "vuejs-accessibility": any;
            };
            readonly languageOptions: {
                readonly sourceType: "module";
                readonly globals: import("eslint").ESLint.Environment["globals"];
            };
        }, {
            readonly name: "vuejs-accessibility:setup:with-files-rules-and-parser";
            readonly files: ["*.vue", "**/*.vue"];
            readonly plugins: {
                readonly "vuejs-accessibility": any;
            };
            readonly languageOptions: {
                readonly parser: any;
                readonly sourceType: "module";
                readonly globals: import("eslint").ESLint.Environment["globals"];
            };
            readonly rules: {
                "vuejs-accessibility/alt-text": "error";
                "vuejs-accessibility/anchor-has-content": "error";
                "vuejs-accessibility/aria-props": "error";
                "vuejs-accessibility/aria-role": "error";
                "vuejs-accessibility/aria-unsupported-elements": "error";
                "vuejs-accessibility/click-events-have-key-events": "error";
                "vuejs-accessibility/form-control-has-label": "error";
                "vuejs-accessibility/heading-has-content": "error";
                "vuejs-accessibility/iframe-has-title": "error";
                "vuejs-accessibility/interactive-supports-focus": "error";
                "vuejs-accessibility/label-has-for": "error";
                "vuejs-accessibility/media-has-caption": "error";
                "vuejs-accessibility/mouse-events-have-key-events": "error";
                "vuejs-accessibility/no-access-key": "error";
                "vuejs-accessibility/no-autofocus": "error";
                "vuejs-accessibility/no-distracting-elements": "error";
                "vuejs-accessibility/no-redundant-roles": "error";
                "vuejs-accessibility/no-static-element-interactions": "error";
                "vuejs-accessibility/role-has-required-aria-props": "error";
                "vuejs-accessibility/tabindex-no-positive": "error";
            };
        }];
    };
    rules: {
        "alt-text": import("eslint").Rule.RuleModule;
        "anchor-has-content": import("eslint").Rule.RuleModule;
        "aria-props": import("eslint").Rule.RuleModule;
        "aria-role": import("eslint").Rule.RuleModule;
        "aria-unsupported-elements": import("eslint").Rule.RuleModule;
        "click-events-have-key-events": import("eslint").Rule.RuleModule;
        "form-control-has-label": import("eslint").Rule.RuleModule;
        "heading-has-content": import("eslint").Rule.RuleModule;
        "iframe-has-title": import("eslint").Rule.RuleModule;
        "interactive-supports-focus": import("./rules/interactive-supports-focus").InteractiveSupportsFocus;
        "label-has-for": import("eslint").Rule.RuleModule;
        "media-has-caption": import("eslint").Rule.RuleModule;
        "mouse-events-have-key-events": import("eslint").Rule.RuleModule;
        "no-access-key": import("eslint").Rule.RuleModule;
        "no-aria-hidden-on-focusable": import("eslint").Rule.RuleModule;
        "no-autofocus": import("eslint").Rule.RuleModule;
        "no-distracting-elements": import("eslint").Rule.RuleModule;
        "no-onchange": import("eslint").Rule.RuleModule;
        "no-redundant-roles": import("eslint").Rule.RuleModule;
        "no-role-presentation-on-focusable": import("eslint").Rule.RuleModule;
        "no-static-element-interactions": import("eslint").Rule.RuleModule;
        "role-has-required-aria-props": import("eslint").Rule.RuleModule;
        "tabindex-no-positive": import("eslint").Rule.RuleModule;
    };
};
export = _default;
