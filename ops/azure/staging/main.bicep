targetScope = 'resourceGroup'

@description('Azure region. Must match the audited production region unless explicitly reviewed.')
param location string

param appServicePlanName string = 'plan-sayaraforce-staging'
param webAppName string = 'app-sayaraforce-staging'
param mysqlServerName string
param mysqlDatabaseName string = 'sayaraforce_staging'
param storageAccountName string = 'stsayaraforcestaging'
param keyVaultName string
param logAnalyticsWorkspaceName string = 'log-sayaraforce-staging'
param applicationInsightsName string = 'appi-sayaraforce-staging'
param virtualNetworkName string = 'vnet-sayaraforce-staging'
param mysqlAdministratorLogin string = 'sayaraforce_staging_app'

@secure()
param mysqlAdministratorPassword string

@secure()
param appKey string

@secure()
param webhookVerificationToken string

@secure()
param platformAdminPassword string

@secure()
param garageAdminPassword string

@secure()
param employeePassword string

@secure()
param tenantBAdminPassword string

@secure()
param productionDatabaseHostDenylist string

@secure()
param productionWabaIdDenylist string = ''

@secure()
param productionPhoneNumberIdDenylist string = ''

@description('Object ID of a dedicated GitHub OIDC deployment principal. Leave empty to create no deployment role assignment.')
param deploymentPrincipalObjectId string = ''

param deploymentIdentityName string = 'id-sayaraforce-staging-deploy'
param githubOidcSubject string = 'repo:sam-paulstechnologies/garagecrm:environment:sayaraforce-staging'

var tags = {
  application: 'SayaraForce'
  environment: 'staging'
  dataClassification: 'synthetic-only'
  productionIsolation: 'required'
}
var fileShareName = 'sayaraforce-staging'
var mysqlPrivateDnsZoneName = '${mysqlServerName}.private.mysql.database.azure.com'
var appSubnetId = resourceId('Microsoft.Network/virtualNetworks/subnets', virtualNetworkName, 'appservice-staging')
var mysqlSubnetId = resourceId('Microsoft.Network/virtualNetworks/subnets', virtualNetworkName, 'mysql-staging')
var keyVaultReference = '@Microsoft.KeyVault(VaultName=${keyVaultName};SecretName='
var initialWebHost = '${webAppName}.azurewebsites.net'

resource plan 'Microsoft.Web/serverfarms@2024-04-01' = {
  name: appServicePlanName
  location: location
  tags: tags
  kind: 'linux'
  sku: {
    name: 'B1'
    tier: 'Basic'
    size: 'B1'
    capacity: 1
  }
  properties: {
    reserved: true
    perSiteScaling: false
    zoneRedundant: false
  }
}

resource vnet 'Microsoft.Network/virtualNetworks@2024-05-01' = {
  name: virtualNetworkName
  location: location
  tags: tags
  properties: {
    addressSpace: {
      addressPrefixes: ['10.42.0.0/16']
    }
    subnets: [
      {
        name: 'appservice-staging'
        properties: {
          addressPrefix: '10.42.1.0/24'
          delegations: [
            {
              name: 'appservice-delegation'
              properties: {
                serviceName: 'Microsoft.Web/serverFarms'
              }
            }
          ]
        }
      }
      {
        name: 'mysql-staging'
        properties: {
          addressPrefix: '10.42.2.0/24'
          delegations: [
            {
              name: 'mysql-delegation'
              properties: {
                serviceName: 'Microsoft.DBforMySQL/flexibleServers'
              }
            }
          ]
        }
      }
    ]
  }
}

resource mysqlPrivateDns 'Microsoft.Network/privateDnsZones@2024-06-01' = {
  name: mysqlPrivateDnsZoneName
  location: 'global'
  tags: tags
}

resource mysqlPrivateDnsLink 'Microsoft.Network/privateDnsZones/virtualNetworkLinks@2024-06-01' = {
  parent: mysqlPrivateDns
  name: 'link-sayaraforce-staging'
  location: 'global'
  tags: tags
  properties: {
    registrationEnabled: false
    virtualNetwork: {
      id: vnet.id
    }
  }
}

resource mysql 'Microsoft.DBforMySQL/flexibleServers@2023-12-30' = {
  name: mysqlServerName
  location: location
  tags: tags
  sku: {
    name: 'Standard_B1ms'
    tier: 'Burstable'
  }
  properties: {
    administratorLogin: mysqlAdministratorLogin
    administratorLoginPassword: mysqlAdministratorPassword
    version: '8.0.21'
    storage: {
      storageSizeGB: 32
      autoGrow: 'Enabled'
    }
    backup: {
      backupRetentionDays: 7
      geoRedundantBackup: 'Disabled'
    }
    highAvailability: {
      mode: 'Disabled'
    }
    network: {
      delegatedSubnetResourceId: mysqlSubnetId
      privateDnsZoneResourceId: mysqlPrivateDns.id
    }
  }
  dependsOn: [
    vnet
    mysqlPrivateDnsLink
  ]
}

resource database 'Microsoft.DBforMySQL/flexibleServers/databases@2023-12-30' = {
  parent: mysql
  name: mysqlDatabaseName
  properties: {
    charset: 'utf8mb4'
    collation: 'utf8mb4_unicode_ci'
  }
}

resource storage 'Microsoft.Storage/storageAccounts@2023-05-01' = {
  name: storageAccountName
  location: location
  tags: tags
  sku: {
    name: 'Standard_LRS'
  }
  kind: 'StorageV2'
  properties: {
    allowBlobPublicAccess: false
    allowSharedKeyAccess: true
    defaultToOAuthAuthentication: true
    minimumTlsVersion: 'TLS1_2'
    publicNetworkAccess: 'Enabled'
    supportsHttpsTrafficOnly: true
  }
}

resource fileService 'Microsoft.Storage/storageAccounts/fileServices@2023-05-01' = {
  parent: storage
  name: 'default'
}

resource fileShare 'Microsoft.Storage/storageAccounts/fileServices/shares@2023-05-01' = {
  parent: fileService
  name: fileShareName
  properties: {
    accessTier: 'TransactionOptimized'
    shareQuota: 20
    enabledProtocols: 'SMB'
  }
}

resource logs 'Microsoft.OperationalInsights/workspaces@2023-09-01' = {
  name: logAnalyticsWorkspaceName
  location: location
  tags: tags
  properties: {
    sku: {
      name: 'PerGB2018'
    }
    retentionInDays: 30
    features: {
      enableLogAccessUsingOnlyResourcePermissions: true
    }
    publicNetworkAccessForIngestion: 'Enabled'
    publicNetworkAccessForQuery: 'Enabled'
  }
}

resource insights 'Microsoft.Insights/components@2020-02-02' = {
  name: applicationInsightsName
  location: location
  tags: tags
  kind: 'web'
  properties: {
    Application_Type: 'web'
    WorkspaceResourceId: logs.id
    IngestionMode: 'LogAnalytics'
    publicNetworkAccessForIngestion: 'Enabled'
    publicNetworkAccessForQuery: 'Enabled'
  }
}

resource vault 'Microsoft.KeyVault/vaults@2023-07-01' = {
  name: keyVaultName
  location: location
  tags: tags
  properties: {
    tenantId: subscription().tenantId
    enableRbacAuthorization: true
    enablePurgeProtection: true
    enableSoftDelete: true
    softDeleteRetentionInDays: 30
    publicNetworkAccess: 'Enabled'
    sku: {
      family: 'A'
      name: 'standard'
    }
  }
}

resource deploymentIdentity 'Microsoft.ManagedIdentity/userAssignedIdentities@2023-01-31' = {
  name: deploymentIdentityName
  location: location
  tags: tags
}

resource githubFederatedCredential 'Microsoft.ManagedIdentity/userAssignedIdentities/federatedIdentityCredentials@2023-01-31' = {
  parent: deploymentIdentity
  name: 'github-sayaraforce-staging'
  properties: {
    issuer: 'https://token.actions.githubusercontent.com'
    subject: githubOidcSubject
    audiences: [
      'api://AzureADTokenExchange'
    ]
  }
}

resource appKeySecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'app-key'
  properties: { value: appKey }
}
resource dbPasswordSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'mysql-app-password'
  properties: { value: mysqlAdministratorPassword }
}
resource webhookTokenSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'meta-webhook-verification-token'
  properties: { value: webhookVerificationToken }
}
resource platformPasswordSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'platform-admin-initial-password'
  properties: { value: platformAdminPassword }
}
resource garagePasswordSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'garage-admin-initial-password'
  properties: { value: garageAdminPassword }
}
resource employeePasswordSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'employee-initial-password'
  properties: { value: employeePassword }
}
resource tenantBPasswordSecret 'Microsoft.KeyVault/vaults/secrets@2023-07-01' = {
  parent: vault
  name: 'tenant-b-admin-initial-password'
  properties: { value: tenantBAdminPassword }
}

resource web 'Microsoft.Web/sites@2024-04-01' = {
  name: webAppName
  location: location
  tags: tags
  kind: 'app,linux'
  identity: {
    type: 'SystemAssigned'
  }
  properties: {
    serverFarmId: plan.id
    httpsOnly: true
    publicNetworkAccess: 'Enabled'
    virtualNetworkSubnetId: appSubnetId
    vnetRouteAllEnabled: true
    siteConfig: {
      linuxFxVersion: 'PHP|8.3'
      alwaysOn: true
      ftpsState: 'Disabled'
      healthCheckPath: '/healthz'
      http20Enabled: true
      httpLoggingEnabled: true
      minTlsVersion: '1.2'
      scmMinTlsVersion: '1.2'
      remoteDebuggingEnabled: false
      vnetRouteAllEnabled: true
      appCommandLine: 'cp /home/site/wwwroot/ops/azure/staging/nginx-default /etc/nginx/sites-available/default && service nginx reload'
      azureStorageAccounts: {
        stagingfiles: {
          type: 'AzureFiles'
          accountName: storage.name
          shareName: fileShare.name
          accessKey: storage.listKeys().keys[0].value
          mountPath: '/mount/sayaraforce-staging'
          protocol: 'Smb'
        }
      }
      appSettings: [
        { name: 'APP_ENV', value: 'staging' }
        { name: 'APP_DEBUG', value: 'false' }
        { name: 'APP_NAME', value: 'SayaraForce Staging' }
        { name: 'APP_URL', value: 'https://${initialWebHost}' }
        { name: 'APP_KEY', value: '${keyVaultReference}app-key)' }
        { name: 'LOG_CHANNEL', value: 'stderr' }
        { name: 'LOG_LEVEL', value: 'info' }
        { name: 'LOG_STDERR_FORMATTER', value: 'Monolog\\Formatter\\JsonFormatter' }
        { name: 'DB_CONNECTION', value: 'mysql' }
        { name: 'DB_HOST', value: mysql.properties.fullyQualifiedDomainName }
        { name: 'DB_PORT', value: '3306' }
        { name: 'DB_DATABASE', value: mysqlDatabaseName }
        { name: 'DB_USERNAME', value: mysqlAdministratorLogin }
        { name: 'DB_PASSWORD', value: '${keyVaultReference}mysql-app-password)' }
        { name: 'MYSQL_ATTR_SSL_CA', value: '/etc/ssl/certs/ca-certificates.crt' }
        { name: 'CACHE_STORE', value: 'database' }
        { name: 'CACHE_PREFIX', value: 'sayaraforce_staging_cache' }
        { name: 'QUEUE_CONNECTION', value: 'database' }
        { name: 'DB_QUEUE_CONNECTION', value: 'mysql' }
        { name: 'DB_QUEUE_TABLE', value: 'queue_jobs' }
        { name: 'QUEUE_FAILED_TABLE', value: 'failed_jobs' }
        { name: 'SESSION_DRIVER', value: 'database' }
        { name: 'SESSION_COOKIE', value: 'sayaraforce_staging_session' }
        { name: 'SESSION_SECURE_COOKIE', value: 'true' }
        { name: 'SESSION_DOMAIN', value: initialWebHost }
        { name: 'TRUSTED_PROXIES', value: '*' }
        { name: 'FILESYSTEM_DISK', value: 'staging' }
        { name: 'STAGING_STORAGE_PATH', value: '/mount/sayaraforce-staging' }
        { name: 'MAIL_MAILER', value: 'log' }
        { name: 'MAIL_FROM_ADDRESS', value: 'staging-no-reply@sayaraforce.test' }
        { name: 'MAIL_FROM_NAME', value: 'SayaraForce Staging' }
        { name: 'META_WHATSAPP_VERIFY_TOKEN', value: '${keyVaultReference}meta-webhook-verification-token)' }
        { name: 'META_VERIFY_TOKEN', value: '${keyVaultReference}meta-webhook-verification-token)' }
        { name: 'STAGING_EXPECTED_HOST', value: initialWebHost }
        { name: 'STAGING_EXPECTED_DB_DATABASE', value: mysqlDatabaseName }
        { name: 'STAGING_SCHEMA_BASELINE_APPROVED', value: 'true' }
        { name: 'STAGING_PRODUCTION_APP_URL_DENYLIST', value: 'https://sayaraforce.com,https://app.sayaraforce.com' }
        { name: 'STAGING_PRODUCTION_DB_HOST_DENYLIST', value: productionDatabaseHostDenylist }
        { name: 'STAGING_META_PRODUCTION_WABA_ID_DENYLIST', value: productionWabaIdDenylist }
        { name: 'STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST', value: productionPhoneNumberIdDenylist }
        { name: 'STAGING_META_ALLOWED_WABA_IDS', value: '' }
        { name: 'STAGING_META_ALLOWED_PHONE_NUMBER_IDS', value: '' }
        { name: 'STAGING_ALLOW_LEGACY_COMPANY_RESOLUTION', value: 'false' }
        { name: 'STAGING_WHATSAPP_OUTBOUND_ENABLED', value: 'false' }
        { name: 'STAGING_SMS_OUTBOUND_ENABLED', value: 'false' }
        { name: 'STAGING_MESSAGE_RECIPIENT_ALLOWLIST', value: '' }
        { name: 'STAGING_EMAIL_RECIPIENT_ALLOWLIST', value: '' }
        { name: 'STAGING_EMAIL_DOMAIN_ALLOWLIST', value: '' }
        { name: 'STAGING_PLATFORM_ADMIN_EMAIL', value: 'platform-admin@staging.sayaraforce.test' }
        { name: 'STAGING_PLATFORM_ADMIN_PASSWORD', value: '${keyVaultReference}platform-admin-initial-password)' }
        { name: 'STAGING_GARAGE_ADMIN_EMAIL', value: 'garage-admin@staging.sayaraforce.test' }
        { name: 'STAGING_GARAGE_ADMIN_PASSWORD', value: '${keyVaultReference}garage-admin-initial-password)' }
        { name: 'STAGING_EMPLOYEE_EMAIL', value: 'employee@staging.sayaraforce.test' }
        { name: 'STAGING_EMPLOYEE_PASSWORD', value: '${keyVaultReference}employee-initial-password)' }
        { name: 'STAGING_TENANT_B_ADMIN_EMAIL', value: 'tenant-b-admin@staging.sayaraforce.test' }
        { name: 'STAGING_TENANT_B_ADMIN_PASSWORD', value: '${keyVaultReference}tenant-b-admin-initial-password)' }
        { name: 'DEPLOYED_BRANCH', value: 'not-deployed' }
        { name: 'DEPLOYED_COMMIT', value: 'not-deployed' }
        { name: 'DEPLOYED_AT', value: 'not-deployed' }
        { name: 'APPLICATIONINSIGHTS_CONNECTION_STRING', value: insights.properties.ConnectionString }
        { name: 'ApplicationInsightsAgent_EXTENSION_VERSION', value: '~3' }
        { name: 'WEBSITE_VNET_ROUTE_ALL', value: '1' }
        { name: 'WEBSITE_HTTPLOGGING_RETENTION_DAYS', value: '7' }
        { name: 'WEBJOBS_STOPPED', value: '0' }
        { name: 'WEBJOBS_DISABLE_SCHEDULE', value: '0' }
        { name: 'SCM_DO_BUILD_DURING_DEPLOYMENT', value: 'false' }
      ]
    }
  }
  dependsOn: [
    vnet
    database
    appKeySecret
    dbPasswordSecret
    webhookTokenSecret
    platformPasswordSecret
    garagePasswordSecret
    employeePasswordSecret
    tenantBPasswordSecret
  ]
}

resource webLogs 'Microsoft.Web/sites/config@2024-04-01' = {
  parent: web
  name: 'logs'
  properties: {
    applicationLogs: {
      fileSystem: { level: 'Information' }
    }
    httpLogs: {
      fileSystem: {
        enabled: true
        retentionInDays: 7
        retentionInMb: 35
      }
    }
    detailedErrorMessages: { enabled: false }
    failedRequestsTracing: { enabled: false }
  }
}

resource scmPolicy 'Microsoft.Web/sites/basicPublishingCredentialsPolicies@2024-04-01' = {
  parent: web
  name: 'scm'
  properties: { allow: false }
}

resource ftpPolicy 'Microsoft.Web/sites/basicPublishingCredentialsPolicies@2024-04-01' = {
  parent: web
  name: 'ftp'
  properties: { allow: false }
}

resource vaultSecretsUser 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(vault.id, web.id, 'key-vault-secrets-user')
  scope: vault
  properties: {
    roleDefinitionId: subscriptionResourceId('Microsoft.Authorization/roleDefinitions', '4633458b-17de-408a-b874-0445c86b69e6')
    principalId: web.identity.principalId
    principalType: 'ServicePrincipal'
  }
}

resource stagingDeploymentRole 'Microsoft.Authorization/roleAssignments@2022-04-01' = if (!empty(deploymentPrincipalObjectId)) {
  name: guid(web.id, deploymentPrincipalObjectId, 'staging-website-contributor')
  scope: web
  properties: {
    roleDefinitionId: subscriptionResourceId('Microsoft.Authorization/roleDefinitions', 'de139f84-1756-47ae-9be6-808fbbe84772')
    principalId: deploymentPrincipalObjectId
    principalType: 'ServicePrincipal'
  }
}

resource managedStagingDeploymentRole 'Microsoft.Authorization/roleAssignments@2022-04-01' = {
  name: guid(web.id, deploymentIdentity.id, 'staging-website-contributor')
  scope: web
  properties: {
    roleDefinitionId: subscriptionResourceId('Microsoft.Authorization/roleDefinitions', 'de139f84-1756-47ae-9be6-808fbbe84772')
    principalId: deploymentIdentity.properties.principalId
    principalType: 'ServicePrincipal'
  }
}

output webAppId string = web.id
output webAppHostname string = web.properties.defaultHostName
output webAppPrincipalId string = web.identity.principalId
output mysqlServerId string = mysql.id
output mysqlHostname string = mysql.properties.fullyQualifiedDomainName
output storageAccountId string = storage.id
output keyVaultId string = vault.id
output logAnalyticsWorkspaceId string = logs.id
output applicationInsightsId string = insights.id
output deploymentIdentityClientId string = deploymentIdentity.properties.clientId
output deploymentIdentityPrincipalId string = deploymentIdentity.properties.principalId
