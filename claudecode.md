# ============================================================================
# CLAUDE CODE COMPREHENSIVE SKILLS FILE
# Unified Skills for: Claude Code, Antigravity, Stitch, GitHub Copilot
# ============================================================================

version: "2.0.0"
metadata:
  name: "claude-code-unified-skills"
  description: "Comprehensive skills configuration for advanced AI coding assistant"
  author: "Claude Code"
  last_updated: "2026-07-25"
  compatibility:
    - claude_code
    - antigravity
    - stitch
    - github_copilot

# ============================================================================
# CORE SKILLS
# ============================================================================

skills:
  # --------------------------------------------------------------------------
  # 1. CODE GENERATION & COMPLETION
  # --------------------------------------------------------------------------
  code_generation:
    enabled: true
    priority: high
    capabilities:
      - name: "autocomplete"
        description: "Intelligent code completion based on context"
        features:
          - context_aware_completion
          - multi_line_completion
          - type_inference
          - pattern_recognition
          - snippet_expansion
        languages:
          - python
          - javascript
          - typescript
          - java
          - csharp
          - cpp
          - go
          - rust
          - ruby
          - php
          - swift
          - kotlin
          - scala
          - r
          - sql
          - html
          - css
          - bash
          - powershell
      
      - name: "code_generation"
        description: "Generate complete code from natural language descriptions"
        features:
          - function_generation
          - class_generation
          - module_generation
          - algorithm_implementation
          - api_implementation
          - database_queries
          - ui_components
          - test_generation
        parameters:
          style: "clean"
          comments: true
          type_hints: true
          error_handling: true
      
      - name: "boilerplate_generation"
        description: "Generate project scaffolding and boilerplate code"
        features:
          - project_structure
          - configuration_files
          - build_scripts
          - docker_files
          - ci_cd_pipelines
          - readme_files
          - license_files

  # --------------------------------------------------------------------------
  # 2. CODE ANALYSIS & REVIEW
  # --------------------------------------------------------------------------
  code_analysis:
    enabled: true
    priority: high
    capabilities:
      - name: "static_analysis"
        description: "Analyze code for potential issues"
        features:
          - syntax_checking
          - type_checking
          - linting
          - code_smell_detection
          - complexity_analysis
          - dependency_analysis
          - unused_code_detection
        tools:
          - pylint
          - eslint
          - flake8
          - mypy
          - sonarqube
      
      - name: "code_review"
        description: "Comprehensive code review with suggestions"
        features:
          - best_practices_check
          - security_vulnerabilities
          - performance_issues
          - maintainability_assessment
          - readability_improvements
          - design_pattern_suggestions
          - refactoring_opportunities
        output_format:
          - severity: [critical, high, medium, low]
          - line_numbers: true
          - suggestions: true
          - examples: true
      
      - name: "code_quality_metrics"
        description: "Measure and report code quality metrics"
        metrics:
          - cyclomatic_complexity
          - cognitive_complexity
          - lines_of_code
          - code_duplication
          - test_coverage
          - documentation_coverage
          - maintainability_index

  # --------------------------------------------------------------------------
  # 3. DEBUGGING & TROUBLESHOOTING
  # --------------------------------------------------------------------------
  debugging:
    enabled: true
    priority: critical
    capabilities:
      - name: "error_detection"
        description: "Identify and explain errors in code"
        features:
          - syntax_errors
          - runtime_errors
          - logical_errors
          - type_errors
          - import_errors
          - configuration_errors
        analysis:
          - error_message_parsing
          - stack_trace_analysis
          - root_cause_identification
          - fix_suggestions
      
      - name: "debug_assistance"
        description: "Help debug complex issues"
        features:
          - breakpoint_suggestions
          - logging_recommendations
          - variable_inspection
          - state_analysis
          - reproduction_steps
          - test_case_generation
        strategies:
          - divide_and_conquer
          - binary_search
          - trace_analysis
          - hypothesis_testing
      
      - name: "performance_debugging"
        description: "Identify and fix performance bottlenecks"
        features:
          - profiling_guidance
          - memory_leak_detection
          - cpu_bottleneck_analysis
          - io_optimization
          - query_optimization
          - algorithmic_improvements
        tools:
          - profilers
          - memory_analyzers
          - network_analyzers
          - database_analyzers

  # --------------------------------------------------------------------------
  # 4. REFACTORING & OPTIMIZATION
  # --------------------------------------------------------------------------
  refactoring:
    enabled: true
    priority: medium
    capabilities:
      - name: "code_refactoring"
        description: "Improve code structure without changing behavior"
        features:
          - extract_method
          - extract_class
          - extract_variable
          - inline_method
          - rename_symbol
          - move_method
          - pull_up_push_down
          - replace_conditional_with_polymorphism
        principles:
          - single_responsibility
          - open_closed
          - liskov_substitution
          - interface_segregation
          - dependency_inversion
          - dry
          - kiss
          - yagni
      
      - name: "performance_optimization"
        description: "Optimize code for better performance"
        features:
          - algorithm_optimization
          - data_structure_selection
          - caching_strategies
          - parallelization
          - lazy_loading
          - memoization
          - query_optimization
          - resource_management
        metrics:
          - time_complexity
          - space_complexity
          - execution_time
          - memory_usage
          - throughput
      
      - name: "modernization"
        description: "Update code to modern standards"
        features:
          - syntax_updates
          - api_migrations
          - framework_upgrades
          - dependency_updates
          - pattern_modernization
          - legacy_code_migration

  # --------------------------------------------------------------------------
  # 5. TESTING & QUALITY ASSURANCE
  # --------------------------------------------------------------------------
  testing:
    enabled: true
    priority: high
    capabilities:
      - name: "test_generation"
        description: "Generate comprehensive test suites"
        features:
          - unit_tests
          - integration_tests
          - e2e_tests
          - performance_tests
          - security_tests
          - accessibility_tests
        frameworks:
          - pytest
          - jest
          - mocha
          - junit
          - nunit
          - rspec
          - moq
          - unittest
        coverage_goals:
          - line_coverage: 80
          - branch_coverage: 70
          - function_coverage: 90
      
      - name: "test_driven_development"
        description: "Support TDD workflow"
        features:
          - test_first_approach
          - red_green_refactor
          - test_case_design
          - mock_generation
          - assertion_suggestions
          - test_data_generation
      
      - name: "test_maintenance"
        description: "Maintain and improve existing tests"
        features:
          - test_refactoring
          - flaky_test_detection
          - test_coverage_analysis
          - test_performance_optimization
          - test_documentation

  # --------------------------------------------------------------------------
  # 6. DOCUMENTATION
  # --------------------------------------------------------------------------
  documentation:
    enabled: true
    priority: medium
    capabilities:
      - name: "code_documentation"
        description: "Generate comprehensive code documentation"
        features:
          - function_documentation
          - class_documentation
          - module_documentation
          - api_documentation
          - inline_comments
          - docstring_generation
        formats:
          - javadoc
          - jsdoc
          - pydoc
          - xml_doc
          - markdown
          - restructured_text
      
      - name: "technical_documentation"
        description: "Create technical documentation"
        features:
          - architecture_docs
          - design_docs
          - api_specs
          - user_guides
          - deployment_guides
          - troubleshooting_guides
        formats:
          - markdown
          - asciidoc
          - html
          - pdf
          - swagger
          - openapi
      
      - name: "readme_generation"
        description: "Generate project README files"
        sections:
          - title_and_badges
          - description
          - installation
          - usage
          - configuration
          - examples
          - api_reference
          - contributing
          - license

  # --------------------------------------------------------------------------
  # 7. VERSION CONTROL & GIT
  # --------------------------------------------------------------------------
  version_control:
    enabled: true
    priority: high
    capabilities:
      - name: "git_operations"
        description: "Assist with Git workflow"
        features:
          - commit_message_generation
          - branch_naming
          - merge_conflict_resolution
          - rebase_assistance
          - cherry_pick_guidance
          - tag_management
          - release_notes_generation
        conventions:
          - conventional_commits
          - semantic_versioning
          - git_flow
          - github_flow
      
      - name: "code_review_assistance"
        description: "Assist with pull request reviews"
        features:
          - diff_analysis
          - change_impact_assessment
          - review_comments
          - approval_recommendations
          - pr_description_generation
          - changelog_generation
      
      - name: "repository_management"
        description: "Help manage repositories"
        features:
          - gitignore_generation
          - gitattributes_configuration
          - hooks_setup
          - submodule_management
          - lfs_configuration

  # --------------------------------------------------------------------------
  # 8. PROJECT MANAGEMENT
  # --------------------------------------------------------------------------
  project_management:
    enabled: true
    priority: medium
    capabilities:
      - name: "task_breakdown"
        description: "Break down complex tasks"
        features:
          - requirement_analysis
          - task_decomposition
          - dependency_mapping
          - effort_estimation
          - priority_assignment
          - milestone_planning
      
      - name: "architecture_design"
        description: "Design system architecture"
        features:
          - system_design
          - component_design
          - api_design
          - database_design
          - integration_design
          - deployment_architecture
        patterns:
          - microservices
          - monolith
          - serverless
          - event_driven
          - cqrs
          - hexagonal
          - clean_architecture
      
      - name: "technical_debt_management"
        description: "Track and manage technical debt"
        features:
          - debt_identification
          - debt_prioritization
          - debt_documentation
          - remediation_planning
          - impact_assessment

  # --------------------------------------------------------------------------
  # 9. SECURITY
  # --------------------------------------------------------------------------
  security:
    enabled: true
    priority: critical
    capabilities:
      - name: "vulnerability_detection"
        description: "Identify security vulnerabilities"
        features:
          - sql_injection
          - xss_detection
          - csrf_protection
          - authentication_issues
          - authorization_flaws
          - sensitive_data_exposure
          - insecure_dependencies
          - cryptographic_issues
        tools:
          - owasp_top_10
          - cwe_checklist
          - security_best_practices
      
      - name: "security_review"
        description: "Comprehensive security review"
        features:
          - input_validation
          - output_encoding
          - access_control
          - session_management
          - error_handling
          - logging_security
          - configuration_security
        standards:
          - owasp
          - nist
          - iso_27001
          - pci_dss
          - gdpr
      
      - name: "secure_coding"
        description: "Guide secure coding practices"
        features:
          - secure_patterns
          - encryption_implementation
          - secure_api_design
          - secure_configuration
          - secret_management
          - dependency_security

  # --------------------------------------------------------------------------
  # 10. DEVOPS & INFRASTRUCTURE
  # --------------------------------------------------------------------------
  devops:
    enabled: true
    priority: medium
    capabilities:
      - name: "ci_cd_pipelines"
        description: "Create and optimize CI/CD pipelines"
        features:
          - pipeline_design
          - build_optimization
          - test_integration
          - deployment_automation
          - artifact_management
          - environment_management
        platforms:
          - github_actions
          - gitlab_ci
          - jenkins
          - azure_devops
          - circleci
          - travis_ci
      
      - name: "containerization"
        description: "Container and orchestration support"
        features:
          - dockerfile_creation
          - docker_compose
          - kubernetes_manifests
          - helm_charts
          - container_optimization
          - multi_stage_builds
      
      - name: "infrastructure_as_code"
        description: "Infrastructure automation"
        features:
          - terraform_scripts
          - cloudformation_templates
          - ansible_playbooks
          - puppet_manifests
          - chef_recipes
        providers:
          - aws
          - azure
          - gcp
          - digitalocean
          - linode

  # --------------------------------------------------------------------------
  # 11. DATABASE & DATA
  # --------------------------------------------------------------------------
  database:
    enabled: true
    priority: high
    capabilities:
      - name: "schema_design"
        description: "Design database schemas"
        features:
          - entity_relationship_design
          - normalization
          - indexing_strategy
          - constraint_design
          - migration_planning
        types:
          - relational
          - document
          - key_value
          - graph
          - time_series
          - columnar
      
      - name: "query_optimization"
        description: "Optimize database queries"
        features:
          - query_analysis
          - index_recommendations
          - execution_plan_analysis
          - query_rewriting
          - performance_tuning
          - caching_strategies
        databases:
          - postgresql
          - mysql
          - mongodb
          - redis
          - elasticsearch
          - cassandra
          - dynamodb
      
      - name: "data_migration"
        description: "Assist with data migration"
        features:
          - migration_scripts
          - data_validation
          - transformation_logic
          - rollback_plans
          - integrity_checks

  # --------------------------------------------------------------------------
  # 12. API DEVELOPMENT
  # --------------------------------------------------------------------------
  api_development:
    enabled: true
    priority: high
    capabilities:
      - name: "api_design"
        description: "Design RESTful and GraphQL APIs"
        features:
          - resource_design
          - endpoint_design
          - request_response_schemas
          - error_handling
          - versioning_strategy
          - authentication_design
        styles:
          - rest
          - graphql
          - grpc
          - soap
          - websocket
      
      - name: "api_implementation"
        description: "Implement API endpoints"
        features:
          - route_handlers
          - middleware
          - validation
          - serialization
          - rate_limiting
          - caching
        frameworks:
          - express
          - fastapi
          - django_rest
          - spring_boot
          - aspnet_core
          - gin
          - fiber
      
      - name: "api_documentation"
        description: "Document APIs"
        features:
          - openapi_specs
          - swagger_docs
          - postman_collections
          - interactive_docs
          - example_generation
          - sdk_generation

  # --------------------------------------------------------------------------
  # 13. FRONTEND DEVELOPMENT
  # --------------------------------------------------------------------------
  frontend:
    enabled: true
    priority: high
    capabilities:
      - name: "ui_component_development"
        description: "Build UI components"
        features:
          - component_architecture
          - state_management
          - event_handling
          - styling
          - accessibility
          - responsive_design
        frameworks:
          - react
          - vue
          - angular
          - svelte
          - nextjs
          - nuxt
          - astro
      
      - name: "css_and_styling"
        description: "CSS and styling assistance"
        features:
          - css_generation
          - responsive_design
          - animations
          - theming
          - design_systems
          - css_in_js
        methodologies:
          - bem
          - smacss
          - oocss
          - atomic_css
          - utility_first
      
      - name: "frontend_optimization"
        description: "Optimize frontend performance"
        features:
          - bundle_optimization
          - code_splitting
          - lazy_loading
          - image_optimization
          - caching_strategies
          - critical_css
          - web_vitals

  # --------------------------------------------------------------------------
  # 14. BACKEND DEVELOPMENT
  # --------------------------------------------------------------------------
  backend:
    enabled: true
    priority: high
    capabilities:
      - name: "server_development"
        description: "Build backend services"
        features:
          - server_architecture
          - middleware_design
          - request_handling
          - response_generation
          - error_handling
          - logging
        frameworks:
          - nodejs
          - python
          - java
          - csharp
          - go
          - rust
          - ruby
          - php
      
      - name: "microservices"
        description: "Design and implement microservices"
        features:
          - service_decomposition
          - inter_service_communication
          - service_discovery
          - load_balancing
          - circuit_breakers
          - event_bus
        patterns:
          - api_gateway
          - sidecar
          - ambassador
          - saga
          - cqrs
      
      - name: "message_queues"
        description: "Implement message queuing"
        features:
          - queue_design
          - producer_consumer
          - pub_sub
          - message_serialization
          - dead_letter_queues
          - retry_mechanisms
        systems:
          - rabbitmq
          - kafka
          - sqs
          - redis_pubsub
          - nats

  # --------------------------------------------------------------------------
  # 15. MACHINE LEARNING & AI
  # --------------------------------------------------------------------------
  machine_learning:
    enabled: true
    priority: medium
    capabilities:
      - name: "ml_model_development"
        description: "Develop machine learning models"
        features:
          - data_preprocessing
          - feature_engineering
          - model_selection
          - hyperparameter_tuning
          - model_evaluation
          - model_deployment
        frameworks:
          - tensorflow
          - pytorch
          - scikit_learn
          - keras
          - xgboost
          - lightgbm
      
      - name: "data_science"
        description: "Data science assistance"
        features:
          - data_analysis
          - visualization
          - statistical_analysis
          - exploratory_analysis
          - hypothesis_testing
          - reporting
        libraries:
          - pandas
          - numpy
          - matplotlib
          - seaborn
          - plotly
          - scipy
      
      - name: "ai_integration"
        description: "Integrate AI capabilities"
        features:
          - llm_integration
          - embedding_generation
          - vector_databases
          - prompt_engineering
          - ai_agents
          - rag_implementation

  # --------------------------------------------------------------------------
  # 16. MOBILE DEVELOPMENT
  # --------------------------------------------------------------------------
  mobile:
    enabled: true
    priority: medium
    capabilities:
      - name: "mobile_app_development"
        description: "Build mobile applications"
        features:
          - ui_design
          - state_management
          - navigation
          - api_integration
          - offline_support
          - push_notifications
        frameworks:
          - react_native
          - flutter
          - swift
          - kotlin
          - xamarin
          - ionic
      
      - name: "mobile_optimization"
        description: "Optimize mobile apps"
        features:
          - performance_optimization
          - battery_optimization
          - memory_management
          - network_optimization
          - storage_optimization
          - size_reduction

  # --------------------------------------------------------------------------
  # 17. CLOUD SERVICES
  # --------------------------------------------------------------------------
  cloud:
    enabled: true
    priority: medium
    capabilities:
      - name: "cloud_architecture"
        description: "Design cloud architectures"
        features:
          - architecture_design
          - service_selection
          - cost_optimization
          - high_availability
          - disaster_recovery
          - scalability_design
        providers:
          - aws
          - azure
          - gcp
          - ibm_cloud
          - oracle_cloud
      
      - name: "cloud_services"
        description: "Implement cloud services"
        features:
          - compute_services
          - storage_services
          - database_services
          - networking
          - security_services
          - monitoring_services
          - ai_services

  # --------------------------------------------------------------------------
  # 18. BLOCKCHAIN & WEB3
  # --------------------------------------------------------------------------
  blockchain:
    enabled: true
    priority: low
    capabilities:
      - name: "smart_contract_development"
        description: "Develop smart contracts"
        features:
          - contract_design
          - solidity_development
          - testing
          - security_auditing
          - gas_optimization
          - deployment
        platforms:
          - ethereum
          - polygon
          - binance_smart_chain
          - avalanche
          - arbitrum
      
      - name: "dapp_development"
        description: "Build decentralized applications"
        features:
          - frontend_integration
          - wallet_integration
          - transaction_handling
          - event_listening
          - state_management

  # --------------------------------------------------------------------------
  # 19. GAME DEVELOPMENT
  # --------------------------------------------------------------------------
  game_development:
    enabled: true
    priority: low
    capabilities:
      - name: "game_logic"
        description: "Implement game logic"
        features:
          - game_mechanics
          - physics_simulation
          - ai_behavior
          - pathfinding
          - collision_detection
          - state_machines
        engines:
          - unity
          - unreal
          - godot
          - pygame
          - phaser

  # --------------------------------------------------------------------------
  # 20. EMBEDDED SYSTEMS
  # --------------------------------------------------------------------------
  embedded:
    enabled: true
    priority: low
    capabilities:
      - name: "embedded_programming"
        description: "Program embedded systems"
        features:
          - hardware_interface
          - driver_development
          - real_time_programming
          - interrupt_handling
          - memory_management
          - power_optimization
        platforms:
          - arduino
          - raspberry_pi
          - esp32
          - stm32
          - arm

# ============================================================================
# ANTI-GRAVITY SKILLS (Advanced AI Capabilities)
# ============================================================================

antigravity_skills:
  enabled: true
  description: "Advanced AI capabilities beyond standard coding"
  
  capabilities:
    - name: "contextual_understanding"
      description: "Deep understanding of project context"
      features:
        - project_wide_context
        - codebase_navigation
        - dependency_tracking
        - impact_analysis
        - pattern_recognition
        - historical_analysis
    
    - name: "creative_problem_solving"
      description: "Creative approaches to complex problems"
      features:
        - alternative_solutions
        - lateral_thinking
        - analogy_based_reasoning
        - constraint_satisfaction
        - optimization_tradeoffs
    
    - name: "learning_adaptation"
      description: "Adapt to user preferences and patterns"
      features:
        - style_learning
        - preference_tracking
        - workflow_optimization
        - habit_recognition
        - personalized_suggestions

# ============================================================================
# STITCH SKILLS (Integration & Orchestration)
# ============================================================================

stitch_skills:
  enabled: true
  description: "Integration and orchestration capabilities"
  
  capabilities:
    - name: "multi_tool_orchestration"
      description: "Coordinate multiple tools and services"
      features:
        - tool_selection
        - workflow_orchestration
        - data_flow_management
        - error_propagation
        - state_synchronization
    
    - name: "api_integration"
      description: "Integrate with external APIs and services"
      features:
        - api_discovery
        - authentication_handling
        - data_transformation
        - error_handling
        - retry_mechanisms
        - rate_limiting
    
    - name: "data_pipeline"
      description: "Build and manage data pipelines"
      features:
        - etl_processes
        - data_transformation
        - validation
        - monitoring
        - error_recovery

# ============================================================================
# GITHUB COPILOT SKILLS (Enhanced Code Intelligence)
# ============================================================================

copilot_skills:
  enabled: true
  description: "Enhanced code intelligence and suggestions"
  
  capabilities:
    - name: "intelligent_suggestions"
      description: "Context-aware code suggestions"
      features:
        - next_line_prediction
        - multi_line_completion
        - function_implementation
        - test_generation
        - documentation_generation
        - refactoring_suggestions
    
    - name: "code_explanation"
      description: "Explain code functionality"
      features:
        - function_explanation
        - algorithm_explanation
        - pattern_explanation
        - complexity_analysis
        - usage_examples
    
    - name: "code_translation"
      description: "Translate code between languages"
      features:
        - language_conversion
        - framework_migration
        - api_translation
        - syntax_conversion
        - idiom_translation

# ============================================================================
# CROSS-CUTTING CONCERNS
# ============================================================================

cross_cutting:
  enabled: true
  
  capabilities:
    - name: "error_handling"
      description: "Comprehensive error handling strategies"
      features:
        - exception_handling
        - error_propagation
        - error_logging
        - error_recovery
        - user_friendly_messages
    
    - name: "logging"
      description: "Implement logging strategies"
      features:
        - log_levels
        - structured_logging
        - log_aggregation
        - log_analysis
        - audit_trails
    
    - name: "configuration_management"
      description: "Manage application configuration"
      features:
        - environment_variables
        - config_files
        - secrets_management
        - feature_flags
        - dynamic_configuration
    
    - name: "monitoring"
      description: "Implement monitoring and observability"
      features:
        - metrics_collection
        - health_checks
        - alerting
        - tracing
        - dashboards

# ============================================================================
# WORKFLOW & COLLABORATION
# ============================================================================

workflow:
  enabled: true
  
  capabilities:
    - name: "pair_programming"
      description: "Assist with pair programming"
      features:
        - real_time_suggestions
        - code_review
        - knowledge_sharing
        - problem_discussion
        - solution_exploration
    
    - name: "code_mentoring"
      description: "Provide coding mentorship"
      features:
        - concept_explanation
        - best_practices
        - code_reviews
        - learning_paths
        - skill_assessment
    
    - name: "team_collaboration"
      description: "Support team collaboration"
      features:
        - code_standards
        - documentation
        - knowledge_base
        - onboarding
        - best_practices

# ============================================================================
# CUSTOMIZATION & EXTENSIBILITY
# ============================================================================

customization:
  enabled: true
  
  capabilities:
    - name: "custom_rules"
      description: "Define custom coding rules"
      features:
        - naming_conventions
        - code_style
        - architecture_rules
        - security_rules
        - performance_rules
    
    - name: "custom_templates"
      description: "Create custom code templates"
      features:
        - file_templates
        - snippet_templates
        - project_templates
        - test_templates
        - documentation_templates
    
    - name: "custom_workflows"
      description: "Define custom workflows"
      features:
        - development_workflow
        - review_workflow
        - deployment_workflow
        - testing_workflow
        - documentation_workflow

# ============================================================================
# SETTINGS & CONFIGURATION
# ============================================================================

settings:
  # General Settings
  general:
    verbosity: "detailed"
    auto_save: true
    auto_format: true
    show_examples: true
    include_tests: true
    generate_docs: true
  
  # Code Style Settings
  code_style:
    indent_size: 2
    indent_type: "spaces"
    max_line_length: 100
    quote_style: "double"
    semicolons: true
    trailing_commas: "es5"
  
  # AI Behavior Settings
  ai_behavior:
    creativity_level: 0.7
    confidence_threshold: 0.8
    context_window: 16000
    max_tokens: 4096
    temperature: 0.3
    top_p: 0.95
  
  # Integration Settings
  integrations:
    github:
      enabled: true
      auto_commit: false
      auto_push: false
    jira:
      enabled: false
    slack:
      enabled: false
    vscode:
      enabled: true
    intellij:
      enabled: true
  
  # Performance Settings
  performance:
    cache_enabled: true
    cache_size: "1GB"
    parallel_processing: true
    max_workers: 4
    timeout: 30

# ============================================================================
# EXECUTION RULES
# ============================================================================

execution_rules:
  - rule: "always_explain"
    description: "Always explain code changes and suggestions"
    enabled: true
  
  - rule: "show_alternatives"
    description: "Show alternative approaches when relevant"
    enabled: true
  
  - rule: "include_examples"
    description: "Include examples in explanations"
    enabled: true
  
  - rule: "consider_edge_cases"
    description: "Consider edge cases in solutions"
    enabled: true
  
  - rule: "follow_best_practices"
    description: "Always follow industry best practices"
    enabled: true
  
  - rule: "security_first"
    description: "Prioritize security in all suggestions"
    enabled: true
  
  - rule: "performance_aware"
    description: "Consider performance implications"
    enabled: true
  
  - rule: "maintainability_focused"
    description: "Prioritize code maintainability"
    enabled: true
  
  - rule: "test_coverage"
    description: "Suggest tests for new code"
    enabled: true
  
  - rule: "documentation_required"
    description: "Generate documentation for public APIs"
    enabled: true

# ============================================================================
# END OF SKILLS FILE
# ============================================================================
