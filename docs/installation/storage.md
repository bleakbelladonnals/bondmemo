# External storage <!-- omit in toc -->

- [Configure external storage](#configure-external-storage)
- [Add S3-compatible storage](#add-s3-compatible-storage)
  - [1. Create a bucket](#1-create-a-bucket)
  - [2. Create limited credentials](#2-create-limited-credentials)
  - [3. Set environment variables](#3-set-environment-variables)
  - [Use another S3 provider](#use-another-s3-provider)
  - [Move avatars to S3](#move-avatars-to-s3)

External object storage is useful when BondMemo runs on a stateless platform or when uploads should not live on the application server. BondMemo supports the Laravel S3 filesystem driver and compatible providers.

> Never paste real access keys into documentation, source files, issues, or commits. Store them in the deployment environment or a secrets manager.

## Configure external storage

Define at least these environment variables:

```dotenv
FILESYSTEM_DISK=s3
AWS_BUCKET=
AWS_DEFAULT_REGION=
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
```

## Add S3-compatible storage

### 1. Create a bucket

Create a bucket with your provider and save its name and region:

```dotenv
AWS_BUCKET=my-bondmemo-bucket
AWS_DEFAULT_REGION=eu-west-3
```

With the AWS CLI:

```sh
aws s3 mb s3://my-bondmemo-bucket
```

### 2. Create limited credentials

Create a dedicated identity for BondMemo. Grant only the bucket operations the application needs; avoid account-wide policies such as `AmazonS3FullAccess` in production.

Use placeholders in local templates:

```dotenv
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
```

For AWS, create and attach an appropriately scoped policy using the IAM console or CLI. Store the returned credentials directly in the deployment environment—do not copy command output into the repository.

### 3. Set environment variables

Enable the S3 disk after the bucket and credentials are configured:

```dotenv
FILESYSTEM_DISK=s3
```

### Use another S3 provider

Set `AWS_ENDPOINT` for an S3-compatible provider such as DigitalOcean Spaces, Scaleway Object Storage, or MinIO:

```dotenv
AWS_ENDPOINT=https://your-s3-compatible-endpoint.example
```

Confirm whether your provider also requires path-style URLs or a custom region before uploading relationship documents.

### Move avatars to S3

If the installation previously used local storage, run the inherited migration command once after validating the destination and backups:

```sh
php artisan monica:moveavatars
```

The command name is retained for backward compatibility with Monica 4.x.
